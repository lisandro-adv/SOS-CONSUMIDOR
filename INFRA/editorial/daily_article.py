#!/usr/bin/env python3
"""Seleciona uma pauta diária e gera um rascunho original, sem publicar."""

import argparse
import hashlib
import html
import json
import os
import re
import sys
import urllib.error
import urllib.request
import unicodedata
from dataclasses import asdict, dataclass
from datetime import date, datetime, timezone
from pathlib import Path


MIN_WORDS = 350
MAX_WORDS = 700
MAX_API_CALLS_PER_RUN = 1
BANNED_JARGON = {
    "data venia", "ex positis", "in casu", "destarte", "outrossim",
    "pretensão autoral", "jurisprudência pátria",
}
BANNED_PROMISES = {
    "elimine todas as suas dívidas", "resultado garantido", "ganho garantido",
    "indenização garantida", "limpe seu nome agora",
}


@dataclass(frozen=True)
class Candidate:
    title: str
    summary: str
    url: str
    source: str
    published_at: str
    category: str
    score: int


def normalize(value: str) -> str:
    value = unicodedata.normalize("NFKD", value or "")
    value = "".join(char for char in value if not unicodedata.combining(char))
    return re.sub(r"\s+", " ", value.casefold()).strip()


TOPIC_WEIGHTS = {
    "endividamento": 10,
    "superendividamento": 10,
    "divida": 9,
    "inadimplencia": 8,
    "bets": 12,
    "apostas": 10,
    "jogo online": 8,
    "credito": 7,
    "emprestimo": 8,
    "consignado": 8,
    "juros": 7,
    "imposto": 8,
    "tributo": 7,
    "imposto de renda": 9,
    "inflacao": 8,
    "aumento de preco": 9,
    "alta de preco": 9,
    "reajuste": 7,
    "tarifa": 7,
    "custo de vida": 9,
    "cesta basica": 8,
    "combustivel": 7,
    "energia": 7,
    "aluguel": 7,
    "salario minimo": 7,
    "consumidor": 8,
    "cobranca": 8,
    "fraude": 10,
    "golpe": 11,
    "pix": 7,
    "falso boleto": 10,
    "roubo de celular": 8,
    "banco": 5,
    "cartao": 6,
    "financiamento": 7,
    "plano de saude": 7,
    "energia eletrica": 6,
    "telefonia": 6,
    "recall": 6,
}

OFF_TOPIC_PENALTIES = {
    "celebridade": -10,
    "futebol": -10,
    "eleicao": -6,
    "partido": -6,
    "guerra": -6,
    "fofoca": -10,
}


def score_text(title: str, summary: str = "") -> int:
    text = normalize(f"{title} {summary}")
    score = sum(weight for term, weight in TOPIC_WEIGHTS.items() if term in text)
    score += sum(weight for term, weight in OFF_TOPIC_PENALTIES.items() if term in text)
    return score


def infer_category(title: str, summary: str = "") -> str:
    text = normalize(f"{title} {summary}")
    categories = {
        "bets_e_apostas": {"bets", "apostas", "jogo online", "cassino"},
        "dividas_e_credito": {"divida", "endividamento", "inadimplencia", "credito", "emprestimo", "juros", "consignado", "financiamento"},
        "impostos_e_economia": {"imposto", "tributo", "imposto de renda", "economia", "renda", "salario minimo"},
        "precos_e_custo_de_vida": {"inflacao", "aumento de preco", "alta de preco", "reajuste", "tarifa", "custo de vida", "cesta basica", "combustivel", "energia", "aluguel"},
        "direitos_do_consumidor": {"consumidor", "cobranca", "produto", "servico", "recall", "plano de saude"},
        "fraudes_e_golpes": {"fraude", "golpe", "clonagem", "pix", "falso boleto", "roubo de celular"},
    }
    ranked = [
        (sum(term in text for term in terms), category)
        for category, terms in categories.items()
    ]
    ranked.sort(reverse=True)
    return ranked[0][1] if ranked and ranked[0][0] else "cotidiano_financeiro"


def candidate_key(candidate: Candidate) -> str:
    return hashlib.sha256(normalize(candidate.url).encode("utf-8")).hexdigest()


def select_candidates(candidates: list[Candidate], seen_urls: set[str], limit: int = 5) -> list[Candidate]:
    unique = {}
    for candidate in candidates:
        if candidate.url in seen_urls or candidate.score <= 0:
            continue
        key = normalize(candidate.title)
        if key not in unique or candidate.score > unique[key].score:
            unique[key] = candidate
    return sorted(
        unique.values(),
        key=lambda item: (-item.score, item.published_at, item.title),
    )[:limit]


def load_sources(path: Path) -> list[dict]:
    return json.loads(path.read_text(encoding="utf-8"))


def collect_candidates(sources: list[dict]) -> list[Candidate]:
    try:
        import feedparser
    except ImportError as exc:
        raise RuntimeError("Dependência feedparser não instalada") from exc

    candidates = []
    for source in sources:
        feed = feedparser.parse(source["feed_url"])
        if getattr(feed, "bozo", False) and not getattr(feed, "entries", []):
            continue
        for entry in feed.entries[: source.get("max_items", 20)]:
            title = html.unescape(re.sub(r"<[^>]+>", " ", entry.get("title", ""))).strip()
            summary = html.unescape(re.sub(r"<[^>]+>", " ", entry.get("summary", ""))).strip()
            url = entry.get("link", "").strip()
            if not title or not url:
                continue
            published_at = entry.get("published", entry.get("updated", ""))
            candidates.append(
                Candidate(
                    title=title[:300],
                    summary=re.sub(r"\s+", " ", summary)[:700],
                    url=url,
                    source=source["name"],
                    published_at=published_at[:100],
                    category=infer_category(title, summary),
                    score=score_text(title, summary) + int(source.get("trust_weight", 0)),
                )
            )
    return candidates


def build_prompt(research: list[Candidate]) -> str:
    packet = "\n\n".join(
        f"FONTE {index}: {item.source}\nTÍTULO: {item.title}\nRESUMO DO FEED: {item.summary}\nURL: {item.url}"
        for index, item in enumerate(research, start=1)
    )
    return f"""Escreva UM rascunho original para o site SOS Consumidor, destinado a pessoas comuns.

REGRAS OBRIGATÓRIAS:
- Tema restrito a direitos do consumidor, finanças pessoais, endividamento, impostos do cotidiano, preços e custo de vida, crédito, juros, empréstimos, bets ou prevenção de golpes.
- Entre {MIN_WORDS} e 650 palavras; nunca ultrapasse {MAX_WORDS}.
- Linguagem cotidiana, frases curtas, tom didático, objetivo, interessante e acolhedor.
- Título forte e verdadeiro, com até 75 caracteres. Não use promessa de resultado ou alarmismo.
- Comece entregando a informação principal. Use no máximo três subtítulos.
- Inclua uma seção curta "O que isso significa para você" e outra "O que fazer agora".
- Toda pauta econômica deve mostrar um impacto concreto no bolso ou na rotina do cidadão brasileiro; não faça análise de mercado para investidores.
- Só afirme fatos presentes no pacote de pesquisa. Se faltarem dados, não complete por suposição.
- Só mencione lei, artigo ou decisão judicial quando houver fonte oficial no pacote. Explique o fundamento em palavras comuns.
- Não copie frases extensas das fontes. Faça síntese original e atribua fatos relevantes.
- Não use juridiquês, não prometa indenização e não ofereça investimento, crédito ou aposta.
- Em bets, adote abordagem de redução de danos e indique ajuda profissional quando houver perda de controle.
- Termine com "Fontes consultadas" e utilize apenas URLs do pacote.

Retorne JSON válido com: title, meta_description, body_html, keywords (lista), sources (lista de URLs) e editorial_note.

PACOTE DE PESQUISA:
{packet}
"""


def call_gemini(prompt: str) -> dict:
    api_key = os.getenv("GEMINI_API_KEY", "")
    if not api_key:
        raise RuntimeError("GEMINI_API_KEY não configurada")
    model = os.getenv("GEMINI_MODEL", "gemini-2.5-flash-lite")
    url = (
        "https://generativelanguage.googleapis.com/v1beta/models/"
        f"{model}:generateContent?key={api_key}"
    )
    payload = {
        "contents": [{"role": "user", "parts": [{"text": prompt}]}],
        "generationConfig": {
            "temperature": 0.2,
            "maxOutputTokens": 1200,
            "responseMimeType": "application/json",
        },
    }
    request = urllib.request.Request(
        url,
        data=json.dumps(payload, ensure_ascii=False).encode("utf-8"),
        headers={"Content-Type": "application/json; charset=utf-8"},
        method="POST",
    )
    try:
        with urllib.request.urlopen(request, timeout=60) as response:
            response_data = json.loads(response.read().decode("utf-8"))
    except (urllib.error.URLError, urllib.error.HTTPError, TimeoutError) as exc:
        raise RuntimeError(f"Falha na API Gemini: {exc}") from exc
    try:
        raw = response_data["candidates"][0]["content"]["parts"][0]["text"]
        return json.loads(raw)
    except (KeyError, IndexError, TypeError, json.JSONDecodeError) as exc:
        raise RuntimeError("Resposta inválida da API Gemini") from exc


def plain_text(html_text: str) -> str:
    return re.sub(r"\s+", " ", html.unescape(re.sub(r"<[^>]+>", " ", html_text or ""))).strip()


def has_long_overlap(output: str, source: str, window: int = 18) -> bool:
    output_words = normalize(output).split()
    source_words = normalize(source).split()
    if len(source_words) < window:
        return False
    output_joined = " ".join(output_words)
    return any(
        " ".join(source_words[index:index + window]) in output_joined
        for index in range(len(source_words) - window + 1)
    )


def validate_draft(draft: dict, research: list[Candidate]) -> list[str]:
    failures = []
    required = {"title", "meta_description", "body_html", "keywords", "sources", "editorial_note"}
    missing = required - set(draft)
    if missing:
        failures.append("campos ausentes: " + ", ".join(sorted(missing)))
        return failures

    title = str(draft["title"]).strip()
    body = plain_text(str(draft["body_html"]))
    words = body.split()
    if not 20 <= len(title) <= 75:
        failures.append("título fora do limite de 20 a 75 caracteres")
    if not MIN_WORDS <= len(words) <= MAX_WORDS:
        failures.append(f"texto com {len(words)} palavras; esperado entre {MIN_WORDS} e {MAX_WORDS}")
    if len(str(draft["meta_description"])) > 160:
        failures.append("meta description excede 160 caracteres")
    normalized = normalize(f"{title} {body}")
    for expression in BANNED_JARGON | BANNED_PROMISES:
        if normalize(expression) in normalized:
            failures.append(f"expressão proibida: {expression}")
    for heading in ("o que isso significa para voce", "o que fazer agora"):
        if heading not in normalized:
            failures.append(f"seção ausente: {heading}")

    allowed_urls = {item.url for item in research}
    sources = draft["sources"] if isinstance(draft["sources"], list) else []
    if not sources:
        failures.append("nenhuma fonte informada")
    if any(url not in allowed_urls for url in sources):
        failures.append("fonte não presente no pacote de pesquisa")
    for item in research:
        if item.summary and has_long_overlap(body, item.summary):
            failures.append(f"possível cópia extensa da fonte: {item.source}")
            break
    return failures


def load_seen(queue_dir: Path) -> set[str]:
    seen = set()
    for path in queue_dir.glob("*.json"):
        try:
            item = json.loads(path.read_text(encoding="utf-8"))
            seen.update(source.get("url", "") for source in item.get("research", []))
        except (OSError, json.JSONDecodeError, TypeError):
            continue
    return seen


def main() -> int:
    parser = argparse.ArgumentParser(description="Gera um rascunho editorial diário sem publicar")
    parser.add_argument("--sources", type=Path, default=Path(__file__).with_name("sources.json"))
    parser.add_argument("--queue", type=Path, required=True)
    parser.add_argument("--generate", action="store_true", help="Chama a API; sem esta opção gera apenas o pacote")
    parser.add_argument("--date", default=date.today().isoformat())
    args = parser.parse_args()

    args.queue.mkdir(parents=True, exist_ok=True)
    output_path = args.queue / f"{args.date}.json"
    if output_path.exists():
        print(f"Já existe pauta ou rascunho para {args.date}", file=sys.stderr)
        return 3

    candidates = collect_candidates(load_sources(args.sources))
    research = select_candidates(candidates, load_seen(args.queue), limit=5)
    if not research:
        print("Nenhuma pauta relevante localizada", file=sys.stderr)
        return 4

    record = {
        "created_at": datetime.now(timezone.utc).isoformat(),
        "publication_date": args.date,
        "status": "research_packet",
        "api_calls": 0,
        "research": [asdict(item) for item in research],
    }
    if args.generate:
        if MAX_API_CALLS_PER_RUN != 1:
            raise RuntimeError("Limite de chamadas inválido")
        record["api_calls"] = 1
        draft = call_gemini(build_prompt(research))
        failures = validate_draft(draft, research)
        record["draft"] = draft
        record["validation_failures"] = failures
        record["status"] = "awaiting_human_review" if not failures else "rejected_by_validator"

    output_path.write_text(json.dumps(record, ensure_ascii=False, indent=2) + "\n", encoding="utf-8")
    print(json.dumps({"output": str(output_path), "status": record["status"], "api_calls": record["api_calls"]}, ensure_ascii=False))
    return 0


if __name__ == "__main__":
    raise SystemExit(main())
