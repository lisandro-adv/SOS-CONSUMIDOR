import hashlib
import json
import logging
import os
import re
import secrets
from contextlib import asynccontextmanager
from pathlib import Path
from typing import Optional

import asyncpg
import httpx
from fastapi import Depends, FastAPI, Header, HTTPException, status
from pydantic import BaseModel, EmailStr, Field


logger = logging.getLogger("sos_ia")

CPF_PATTERN = re.compile(r"(?<!\d)\d{3}\.?\d{3}\.?\d{3}-?\d{2}(?!\d)")
LONG_NUMBER_PATTERN = re.compile(r"(?<!\d)(?:\d[ -]?){13,19}(?!\d)")
SECRET_LABEL_PATTERN = re.compile(
    r"\b(?:senha|password|cvv|c[oó]digo\s+de\s+segurança)\s*[:=]\s*\S{3,}",
    re.IGNORECASE,
)
CONSUMER_SCOPE_TERMS = {
    "assinatura", "banco", "cadastro", "cancelamento", "cartão", "cobrança",
    "compra", "consumidor", "consumo", "contrato", "crédito", "defeito",
    "dívida", "empréstimo", "empresa", "entrega", "estorno", "fatura",
    "financiamento", "fornecedor", "fraude", "garantia", "internet", "loja",
    "negativação", "plano de saúde", "produto", "promoção", "propaganda",
    "reembolso", "renegociação", "seguro", "serviço", "spc", "serasa",
    "superendividamento", "telefonia", "troca", "venda", "voo",
}

DISCLAIMER = "Esta é uma orientação educativa e não substitui a análise individual do caso."
LEGAL_KNOWLEDGE_PATH = Path(__file__).with_name("legal_knowledge.json")
LEGAL_TOPICS = json.loads(LEGAL_KNOWLEDGE_PATH.read_text(encoding="utf-8"))

SYSTEM_PROMPT = f"""Você é o SOS Responde, um orientador educativo brasileiro para pessoas comuns com dúvidas de consumo.

Regras obrigatórias:
- Este fórum é para consumidores. Não escreva petições, pareceres, teses para advogados ou estratégias de processo.
- Responda em português do Brasil, com linguagem simples, acolhedora, objetiva e sem juridiquês.
- Não use expressões como "data venia", "ex positis", "in casu", "outrossim", "destarte", "pretensão autoral" ou "jurisprudência pátria".
- Quando mencionar uma lei ou artigo, explique imediatamente o significado em palavras comuns.
- Use somente os fundamentos e links fornecidos no bloco BASE JURÍDICA REVISADA. Não invente nem complete números de leis, artigos, decisões ou prazos.
- Se a base fornecida não cobrir a pergunta, diga claramente que não é possível confirmar a regra jurídica com a base revisada disponível.
- Não prometa resultado, indenização, retirada de negativação ou redução de dívida.
- Não emita parecer jurídico individual e não diga que o usuário certamente ganhará uma causa.
- Nunca solicite CPF, senha, número completo de cartão, CVV, dados bancários ou documentos.
- Em situação individual que exija análise documental, indique Procon, Defensoria Pública ou advogado de livre escolha.
- Organize a resposta, sempre que fizer sentido, nestas partes curtas: "Resposta direta", "O que a lei diz", "O que fazer agora" e "Fontes oficiais".
- Dê uma resposta curta: no máximo 180 palavras, salvo se for necessário explicar um alerta de segurança.
- Em "Fontes oficiais", cite apenas as fontes presentes na base revisada. Não crie links.
- A resposta deve terminar exatamente com: "{DISCLAIMER}"
"""


class RegisterRequest(BaseModel):
    email: EmailStr
    terms_accepted: bool


class VerifyEmailRequest(BaseModel):
    token: str = Field(min_length=32, max_length=256)


class QuestionRequest(BaseModel):
    question: str = Field(min_length=10, max_length=2000)


class AuthenticatedUser(BaseModel):
    id: str
    email: EmailStr
    daily_limit: int


def token_digest(token: str) -> str:
    return hashlib.sha256(token.encode("utf-8")).hexdigest()


def env_flag(name: str, default: bool = False) -> bool:
    value = os.getenv(name)
    if value is None:
        return default
    return value.strip().lower() in {"1", "true", "yes", "on"}


def verify_internal_test_key(provided_key: Optional[str]) -> None:
    """Gate the temporary test route independently from the public pilot."""
    if not env_flag("ENABLE_INTERNAL_TEST_ENDPOINT", default=False):
        # Deliberately indistinguishable from a route that does not exist.
        raise HTTPException(status_code=status.HTTP_404_NOT_FOUND, detail="Não encontrado.")
    expected_key = os.getenv("INTERNAL_TEST_API_KEY", "")
    if not expected_key or not provided_key or not secrets.compare_digest(provided_key, expected_key):
        raise HTTPException(status_code=status.HTTP_401_UNAUTHORIZED, detail="Não autorizado.")


def contains_sensitive_data(text: str) -> bool:
    return any(
        pattern.search(text)
        for pattern in (CPF_PATTERN, LONG_NUMBER_PATTERN, SECRET_LABEL_PATTERN)
    )


def is_consumer_question(text: str) -> bool:
    normalized = text.casefold()
    topic_terms = {
        keyword.casefold()
        for topic in LEGAL_TOPICS
        for keyword in topic["keywords"]
    }
    return any(term in normalized for term in CONSUMER_SCOPE_TERMS | topic_terms)


def select_legal_topics(question: str, limit: int = 3) -> list[dict]:
    normalized = question.casefold()
    scored = []
    for topic in LEGAL_TOPICS:
        score = sum(1 for keyword in topic["keywords"] if keyword.casefold() in normalized)
        if score:
            scored.append((score, topic["id"], topic))
    scored.sort(key=lambda item: (-item[0], item[1]))
    return [item[2] for item in scored[:limit]]


def build_legal_context(topics: list[dict]) -> str:
    if not topics:
        return (
            "Nenhuma regra específica da base revisada foi localizada para esta pergunta. "
            "Não confirme leis, artigos, prazos ou direitos não presentes na base."
        )
    blocks = []
    for topic in topics:
        blocks.append(
            "\n".join(
                [
                    f"TEMA: {topic['title']}",
                    f"EXPLICAÇÃO REVISADA: {topic['plain_summary']}",
                    f"FUNDAMENTO: {topic['legal_basis']}",
                    f"FONTE OFICIAL: {topic['source_title']} — {topic['source_url']}",
                ]
            )
        )
    return "\n\n".join(blocks)


def public_sources(topics: list[dict]) -> list[dict[str, str]]:
    return [
        {
            "title": topic["source_title"],
            "legal_basis": topic["legal_basis"],
            "url": topic["source_url"],
            "reviewed_at": topic["reviewed_at"],
        }
        for topic in topics
    ]


def ensure_disclaimer(answer: str) -> str:
    cleaned = answer.strip()
    if cleaned.endswith(DISCLAIMER):
        return cleaned
    return f"{cleaned}\n\n{DISCLAIMER}"


def build_ollama_payload(question: str, topics: Optional[list[dict]] = None) -> dict:
    selected_topics = topics if topics is not None else select_legal_topics(question)
    return {
        "model": os.getenv("OLLAMA_MODEL", "qwen3:4b-instruct"),
        "stream": False,
        "keep_alive": -1,
        "messages": [
            {"role": "system", "content": SYSTEM_PROMPT},
            {
                "role": "system",
                "content": "BASE JURÍDICA REVISADA:\n" + build_legal_context(selected_topics),
            },
            {"role": "user", "content": question.strip()},
        ],
        "options": {
            "temperature": 0.2,
            "num_predict": max(80, min(int(os.getenv("OLLAMA_NUM_PREDICT", "180")), 220)),
        },
    }


async def warm_ollama() -> bool:
    ollama_url = os.getenv("OLLAMA_URL", "http://ollama:11434").rstrip("/")
    # Warm the same chat template used in the pilot, rather than only loading
    # model weights. This leaves the fixed consumer-law instructions in the
    # prompt cache and avoids making the first visitor pay the cold-prompt cost.
    payload = build_ollama_payload(
        "Comprei um produto com defeito. Responda somente: pronto.",
        select_legal_topics("Comprei um produto com defeito."),
    )
    payload["options"]["num_predict"] = 1
    try:
        timeout_seconds = max(5, min(int(os.getenv("OLLAMA_WARMUP_TIMEOUT_SECONDS", "90")), 240))
        async with httpx.AsyncClient(timeout=httpx.Timeout(timeout_seconds, connect=5.0)) as client:
            response = await client.post(f"{ollama_url}/api/chat", json=payload)
        response.raise_for_status()
        logger.info("Modelo Ollama pré-carregado e mantido em memória")
        return True
    except (httpx.HTTPError, ValueError, TypeError) as exc:
        logger.warning("Não foi possível pré-carregar o modelo Ollama: %s", exc)
        return False


async def send_verification_email(email: str, token: str) -> None:
    api_key = os.getenv("BREVO_API_KEY", "")
    if not api_key:
        raise HTTPException(
            status_code=status.HTTP_503_SERVICE_UNAVAILABLE,
            detail="Envio de e-mail ainda não configurado.",
        )

    public_url = os.getenv("PUBLIC_APP_URL", "https://forum.sosconsumidor.com.br").rstrip("/")
    verify_url = f"{public_url}/verificar-email?token={token}"
    payload = {
        "sender": {
            "email": os.getenv("EMAIL_FROM", "no-reply@sosconsumidor.com.br"),
            "name": "SOS Consumidor",
        },
        "to": [{"email": email}],
        "subject": "Confirme seu e-mail no Fórum SOS Consumidor",
        "htmlContent": (
            "<p>Olá,</p><p>Para ativar sua conta no Fórum SOS Consumidor, "
            f"<a href=\"{verify_url}\">confirme seu e-mail</a>.</p>"
            "<p>O link expira em 30 minutos. Se você não solicitou este cadastro, ignore esta mensagem.</p>"
        ),
    }
    try:
        async with httpx.AsyncClient(timeout=10) as client:
            response = await client.post(
                "https://api.brevo.com/v3/smtp/email",
                headers={"api-key": api_key, "accept": "application/json"},
                json=payload,
            )
        response.raise_for_status()
    except httpx.HTTPError as exc:
        raise HTTPException(
            status_code=status.HTTP_502_BAD_GATEWAY,
            detail="Não foi possível enviar o e-mail de confirmação. Tente novamente mais tarde.",
        ) from exc


async def ask_ollama(question: str, topics: list[dict]) -> str:
    ollama_url = os.getenv("OLLAMA_URL", "http://ollama:11434").rstrip("/")
    try:
        timeout_seconds = max(10, min(int(os.getenv("OLLAMA_RESPONSE_TIMEOUT_SECONDS", "45")), 120))
        timeout = httpx.Timeout(timeout_seconds, connect=5.0)
        async with httpx.AsyncClient(timeout=timeout) as client:
            response = await client.post(
                f"{ollama_url}/api/chat",
                json=build_ollama_payload(question, topics),
            )
        response.raise_for_status()
        content = response.json().get("message", {}).get("content", "").strip()
        if not content:
            raise ValueError("Ollama retornou resposta vazia")
        return ensure_disclaimer(content)
    except (httpx.HTTPError, ValueError, TypeError) as exc:
        raise HTTPException(
            status_code=status.HTTP_502_BAD_GATEWAY,
            detail="A orientação não está disponível neste momento. Tente novamente mais tarde.",
        ) from exc


async def get_current_user(authorization: Optional[str] = Header(default=None)) -> AuthenticatedUser:
    if not authorization or not authorization.startswith("Bearer "):
        raise HTTPException(status_code=status.HTTP_401_UNAUTHORIZED, detail="Autenticação necessária.")
    raw_token = authorization.removeprefix("Bearer ").strip()
    if len(raw_token) < 32:
        raise HTTPException(status_code=status.HTTP_401_UNAUTHORIZED, detail="Sessão inválida.")

    async with app.state.pool.acquire() as conn:
        row = await conn.fetchrow(
            """
            UPDATE auth_sessions AS s
               SET last_used_at = now()
              FROM users AS u
             WHERE s.token_hash = $1
               AND s.user_id = u.id
               AND s.revoked_at IS NULL
               AND s.expires_at > now()
               AND u.email_verified_at IS NOT NULL
            RETURNING u.id::text, u.email, u.daily_limit
            """,
            token_digest(raw_token),
        )
    if not row:
        raise HTTPException(status_code=status.HTTP_401_UNAUTHORIZED, detail="Sessão inválida ou expirada.")
    return AuthenticatedUser(**dict(row))


async def reserve_daily_question(user: AuthenticatedUser) -> int:
    if user.daily_limit <= 0:
        raise HTTPException(
            status_code=status.HTTP_429_TOO_MANY_REQUESTS,
            detail="Seu limite diário de perguntas foi atingido.",
        )
    async with app.state.pool.acquire() as conn:
        row = await conn.fetchrow(
            """
            INSERT INTO question_usage (user_id, usage_day, question_count)
            VALUES ($1::uuid, CURRENT_DATE, 1)
            ON CONFLICT (user_id, usage_day) DO UPDATE
                SET question_count = question_usage.question_count + 1
              WHERE question_usage.question_count < $2
            RETURNING question_count
            """,
            user.id,
            user.daily_limit,
        )
    if not row:
        raise HTTPException(
            status_code=status.HTTP_429_TOO_MANY_REQUESTS,
            detail="Seu limite diário de perguntas foi atingido.",
        )
    return int(row["question_count"])


async def release_daily_question(user_id: str) -> None:
    async with app.state.pool.acquire() as conn:
        await conn.execute(
            """
            UPDATE question_usage
               SET question_count = GREATEST(question_count - 1, 0)
             WHERE user_id = $1::uuid AND usage_day = CURRENT_DATE
            """,
            user_id,
        )


@asynccontextmanager
async def lifespan(application: FastAPI):
    database_url = os.getenv("DATABASE_URL")
    if database_url:
        application.state.pool = await asyncpg.create_pool(database_url, min_size=1, max_size=5)
    else:
        application.state.pool = await asyncpg.create_pool(
            host=os.getenv("DB_HOST", "postgres"),
            port=int(os.getenv("DB_PORT", "5432")),
            database=os.getenv("DB_NAME", "sos_ia"),
            user=os.getenv("DB_USER", "sos_ia"),
            password=os.environ["POSTGRES_PASSWORD"],
            min_size=1,
            max_size=5,
        )
    async with application.state.pool.acquire() as conn:
        await conn.execute("CREATE EXTENSION IF NOT EXISTS pgcrypto")
        await conn.execute(
            """
            CREATE TABLE IF NOT EXISTS users (
                id UUID PRIMARY KEY DEFAULT gen_random_uuid(),
                email TEXT UNIQUE NOT NULL,
                email_verified_at TIMESTAMPTZ,
                daily_limit SMALLINT NOT NULL DEFAULT 3 CHECK (daily_limit BETWEEN 0 AND 100),
                terms_accepted_at TIMESTAMPTZ,
                terms_version TEXT,
                created_at TIMESTAMPTZ NOT NULL DEFAULT now()
            );
            ALTER TABLE users ADD COLUMN IF NOT EXISTS terms_version TEXT;

            CREATE TABLE IF NOT EXISTS question_usage (
                user_id UUID NOT NULL REFERENCES users(id) ON DELETE CASCADE,
                usage_day DATE NOT NULL DEFAULT CURRENT_DATE,
                question_count SMALLINT NOT NULL DEFAULT 0 CHECK (question_count >= 0),
                PRIMARY KEY (user_id, usage_day)
            );
            CREATE TABLE IF NOT EXISTS email_verification_tokens (
                token_hash CHAR(64) PRIMARY KEY,
                user_id UUID NOT NULL REFERENCES users(id) ON DELETE CASCADE,
                expires_at TIMESTAMPTZ NOT NULL,
                used_at TIMESTAMPTZ,
                created_at TIMESTAMPTZ NOT NULL DEFAULT now()
            );
            CREATE INDEX IF NOT EXISTS email_verification_tokens_user_idx
              ON email_verification_tokens (user_id, expires_at DESC);

            CREATE TABLE IF NOT EXISTS auth_sessions (
                token_hash CHAR(64) PRIMARY KEY,
                user_id UUID NOT NULL REFERENCES users(id) ON DELETE CASCADE,
                expires_at TIMESTAMPTZ NOT NULL,
                last_used_at TIMESTAMPTZ,
                revoked_at TIMESTAMPTZ,
                created_at TIMESTAMPTZ NOT NULL DEFAULT now()
            );
            CREATE INDEX IF NOT EXISTS auth_sessions_user_idx
              ON auth_sessions (user_id, expires_at DESC);
            """
        )
    application.state.ollama_warm = await warm_ollama()
    try:
        yield
    finally:
        await application.state.pool.close()


app = FastAPI(title="SOS Consumidor IA", docs_url=None, redoc_url=None, lifespan=lifespan)


@app.get("/health")
async def health() -> dict[str, str]:
    async with app.state.pool.acquire() as conn:
        await conn.fetchval("SELECT 1")
    return {"status": "ok", "service": "sos-ia-api"}


@app.post("/v1/auth/register", status_code=status.HTTP_202_ACCEPTED)
async def register(payload: RegisterRequest) -> dict[str, str]:
    if not payload.terms_accepted:
        raise HTTPException(status_code=status.HTTP_422_UNPROCESSABLE_ENTITY, detail="É necessário aceitar os termos de uso.")
    if not os.getenv("BREVO_API_KEY", ""):
        raise HTTPException(status_code=status.HTTP_503_SERVICE_UNAVAILABLE, detail="Cadastro ainda não está disponível.")

    email = str(payload.email).strip().lower()
    token = secrets.token_urlsafe(32)
    expires_minutes = int(os.getenv("EMAIL_VERIFY_TOKEN_TTL_MINUTES", "30"))
    terms_version = os.getenv("TERMS_VERSION", "pilot-2026-08-02")
    daily_limit = max(0, min(int(os.getenv("DAILY_FREE_LIMIT", "3")), 100))
    async with app.state.pool.acquire() as conn:
        async with conn.transaction():
            user_id = await conn.fetchval(
                """
                INSERT INTO users (email, daily_limit, terms_accepted_at, terms_version)
                VALUES ($1, $2, now(), $3)
                ON CONFLICT (email) DO UPDATE
                    SET terms_accepted_at = EXCLUDED.terms_accepted_at,
                        terms_version = EXCLUDED.terms_version
                RETURNING id
                """,
                email,
                daily_limit,
                terms_version,
            )
            await conn.execute(
                "DELETE FROM email_verification_tokens WHERE user_id = $1 AND used_at IS NULL",
                user_id,
            )
            await conn.execute(
                """
                INSERT INTO email_verification_tokens (token_hash, user_id, expires_at)
                VALUES ($1, $2, now() + $3::interval)
                """,
                token_digest(token),
                user_id,
                f"{expires_minutes} minutes",
            )

    await send_verification_email(email, token)
    return {"message": "Se o endereço puder ser utilizado, enviamos um link de confirmação."}


@app.post("/v1/auth/verify-email")
async def verify_email(payload: VerifyEmailRequest) -> dict[str, str]:
    session_token = secrets.token_urlsafe(48)
    session_ttl_hours = int(os.getenv("SESSION_TTL_HOURS", "720"))
    async with app.state.pool.acquire() as conn:
        async with conn.transaction():
            row = await conn.fetchrow(
                """
                UPDATE email_verification_tokens AS t
                   SET used_at = now()
                  FROM users AS u
                 WHERE t.token_hash = $1
                   AND t.user_id = u.id
                   AND t.used_at IS NULL
                   AND t.expires_at > now()
                RETURNING u.id
                """,
                token_digest(payload.token),
            )
            if not row:
                raise HTTPException(status_code=status.HTTP_400_BAD_REQUEST, detail="Link inválido ou expirado.")
            await conn.execute("UPDATE users SET email_verified_at = now() WHERE id = $1", row["id"])
            await conn.execute(
                """
                INSERT INTO auth_sessions (token_hash, user_id, expires_at)
                VALUES ($1, $2, now() + $3::interval)
                """,
                token_digest(session_token),
                row["id"],
                f"{session_ttl_hours} hours",
            )
    return {
        "message": "E-mail confirmado. Você já pode usar o Fórum SOS Consumidor.",
        "access_token": session_token,
        "token_type": "bearer",
    }


@app.get("/v1/me")
async def me(user: AuthenticatedUser = Depends(get_current_user)) -> dict:
    return {"id": user.id, "email": str(user.email), "daily_limit": user.daily_limit}


@app.post("/v1/auth/logout", status_code=status.HTTP_204_NO_CONTENT)
async def logout(
    authorization: Optional[str] = Header(default=None),
    user: AuthenticatedUser = Depends(get_current_user),
) -> None:
    del user
    raw_token = authorization.removeprefix("Bearer ").strip() if authorization else ""
    async with app.state.pool.acquire() as conn:
        await conn.execute(
            "UPDATE auth_sessions SET revoked_at = now() WHERE token_hash = $1",
            token_digest(raw_token),
        )


@app.post("/v1/questions")
async def ask_question(
    payload: QuestionRequest,
    user: AuthenticatedUser = Depends(get_current_user),
) -> dict:
    if not env_flag("ENABLE_QUESTIONS", default=False):
        raise HTTPException(
            status_code=status.HTTP_503_SERVICE_UNAVAILABLE,
            detail="O piloto de orientações ainda não foi habilitado.",
        )
    question = payload.question.strip()
    if contains_sensitive_data(question):
        raise HTTPException(
            status_code=status.HTTP_422_UNPROCESSABLE_ENTITY,
            detail="Remova CPF, números completos de cartão, senhas e outros dados sensíveis antes de enviar.",
        )
    if not is_consumer_question(question):
        raise HTTPException(
            status_code=status.HTTP_422_UNPROCESSABLE_ENTITY,
            detail=(
                "O SOS Consumidor responde somente dúvidas sobre relações de consumo. "
                "Para outros assuntos, procure um serviço especializado na área correspondente."
            ),
        )

    used = await reserve_daily_question(user)
    topics = select_legal_topics(question)
    try:
        answer = await ask_ollama(question, topics)
    except HTTPException:
        await release_daily_question(user.id)
        raise

    return {
        "answer": answer,
        "remaining_questions": max(user.daily_limit - used, 0),
        "stored": False,
        "sources": public_sources(topics),
    }


@app.post("/internal/test-question")
async def internal_test_question(
    payload: QuestionRequest,
    x_internal_test_key: Optional[str] = Header(default=None),
) -> dict:
    """Private, non-persistent route used only by the protected test panel.

    It has its own feature flag and shared secret, is reachable only through the
    site-to-AI SSH tunnel, never creates an account and never writes the question
    to PostgreSQL. The public /v1/questions endpoint remains fail-closed.
    """
    verify_internal_test_key(x_internal_test_key)
    question = payload.question.strip()
    if contains_sensitive_data(question):
        raise HTTPException(
            status_code=status.HTTP_422_UNPROCESSABLE_ENTITY,
            detail="Remova CPF, números completos de cartão, senhas e outros dados sensíveis antes de enviar.",
        )
    if not is_consumer_question(question):
        raise HTTPException(
            status_code=status.HTTP_422_UNPROCESSABLE_ENTITY,
            detail="O SOS Consumidor responde somente dúvidas sobre relações de consumo.",
        )

    topics = select_legal_topics(question)
    answer = await ask_ollama(question, topics)
    return {
        "answer": answer,
        "stored": False,
        "sources": public_sources(topics),
    }
