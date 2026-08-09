import hashlib
import asyncio

import pytest
from fastapi import HTTPException

from app.main import (
    SYSTEM_PROMPT,
    DISCLAIMER,
    LEGAL_TOPICS,
    AuthenticatedUser,
    QuestionRequest,
    ask_question,
    build_ollama_payload,
    contains_sensitive_data,
    ensure_disclaimer,
    env_flag,
    is_consumer_question,
    public_sources,
    select_legal_topics,
    token_digest,
    verify_internal_test_key,
)


def test_token_digest_never_returns_raw_token():
    token = "segredo-de-teste-comprido"
    assert token_digest(token) == hashlib.sha256(token.encode("utf-8")).hexdigest()
    assert token not in token_digest(token)


def test_sensitive_data_filter_blocks_cpf_card_and_password():
    assert contains_sensitive_data("Meu CPF é 123.456.789-00")
    assert contains_sensitive_data("Cartão 4111 1111 1111 1111")
    assert contains_sensitive_data("senha: minhaSenha123")


def test_sensitive_data_filter_allows_normal_consumer_question():
    assert not contains_sensitive_data("Paguei a primeira parcela do acordo. Quando a negativação deve ser retirada?")


def test_scope_accepts_consumer_questions_and_rejects_unrelated_topics():
    assert is_consumer_question("Minha compra não chegou e a loja não responde")
    assert is_consumer_question("O banco fez uma cobrança que não reconheço")
    assert not is_consumer_question("Como faço uma receita de bolo de chocolate?")
    assert not is_consumer_question("Quem ganhou o jogo de futebol ontem?")


def test_question_payload_does_not_enable_streaming(monkeypatch):
    monkeypatch.setenv("OLLAMA_MODEL", "modelo-teste")
    payload = build_ollama_payload("Como organizar minhas dívidas?")
    assert payload["model"] == "modelo-teste"
    assert payload["stream"] is False
    assert payload["keep_alive"] == -1
    assert payload["options"]["num_predict"] == 180
    assert payload["messages"][0]["content"] == SYSTEM_PROMPT
    assert "BASE JURÍDICA REVISADA" in payload["messages"][1]["content"]
    assert payload["messages"][2]["content"] == "Como organizar minhas dívidas?"


def test_prompt_is_for_consumers_and_rejects_legalese():
    assert "pessoas comuns" in SYSTEM_PROMPT
    assert "sem juridiquês" in SYSTEM_PROMPT
    assert "Este fórum é para consumidores" in SYSTEM_PROMPT


def test_retrieval_selects_reviewed_consumer_law():
    topics = select_legal_topics("Minha geladeira quebrou depois de vinte dias. Posso pedir a troca?")
    assert topics
    assert topics[0]["id"] == "product_defect"
    assert "arts. 18 e 26" in topics[0]["legal_basis"]
    sources = public_sources(topics)
    assert sources[0]["url"].startswith("https://www.planalto.gov.br/")


def test_unknown_law_does_not_receive_unrelated_source():
    assert select_legal_topics("A Lei Federal 14.999/2025 existe?") == []


def test_legal_knowledge_has_review_metadata_and_official_sources():
    assert LEGAL_TOPICS
    for topic in LEGAL_TOPICS:
        assert topic["reviewed_at"] == "2026-08-03"
        assert "planalto.gov.br" in topic["source_url"]


def test_disclaimer_is_enforced_by_application():
    answer = ensure_disclaimer("Resposta direta: procure o fornecedor.")
    assert answer.endswith(DISCLAIMER)
    assert ensure_disclaimer(answer).count(DISCLAIMER) == 1


def test_feature_flag_is_fail_closed(monkeypatch):
    monkeypatch.delenv("ENABLE_QUESTIONS", raising=False)
    assert env_flag("ENABLE_QUESTIONS") is False
    monkeypatch.setenv("ENABLE_QUESTIONS", "true")
    assert env_flag("ENABLE_QUESTIONS") is True


def test_question_endpoint_is_closed_by_default(monkeypatch):
    monkeypatch.delenv("ENABLE_QUESTIONS", raising=False)
    user = AuthenticatedUser(id="00000000-0000-0000-0000-000000000001", email="teste@example.com", daily_limit=3)
    with pytest.raises(HTTPException) as exc:
        asyncio.run(ask_question(QuestionRequest(question="Como posso organizar minhas dívidas?"), user))
    assert exc.value.status_code == 503


def test_internal_test_endpoint_is_fail_closed_and_requires_exact_key(monkeypatch):
    monkeypatch.delenv("ENABLE_INTERNAL_TEST_ENDPOINT", raising=False)
    with pytest.raises(HTTPException) as exc:
        verify_internal_test_key("qualquer-coisa")
    assert exc.value.status_code == 404

    monkeypatch.setenv("ENABLE_INTERNAL_TEST_ENDPOINT", "true")
    monkeypatch.setenv("INTERNAL_TEST_API_KEY", "chave-de-teste")
    with pytest.raises(HTTPException) as exc:
        verify_internal_test_key("chave-errada")
    assert exc.value.status_code == 401

    verify_internal_test_key("chave-de-teste")
