import unittest

from daily_article import Candidate, build_prompt, score_text, select_candidates, validate_draft


def candidate(title="Famílias enfrentam aumento do endividamento", score=10):
    return Candidate(
        title=title,
        summary="Dados mostram aumento das dívidas das famílias brasileiras.",
        url="https://example.org/fonte",
        source="Fonte de teste",
        published_at="2026-08-03",
        category="dividas_e_credito",
        score=score,
    )


class DailyArticleTests(unittest.TestCase):
    def test_priority_topics_receive_high_score(self):
        self.assertGreater(score_text("Endividamento e juros do empréstimo"), 20)
        self.assertGreater(score_text("Bets aumentam dívidas das famílias"), 20)
        self.assertGreater(score_text("Golpe do falso boleto causa prejuízo ao consumidor"), 20)
        self.assertGreater(score_text("Inflação e aumento de preço da cesta básica"), 20)
        self.assertLessEqual(score_text("Resultado do jogo de futebol"), 0)

    def test_seen_or_irrelevant_candidates_are_removed(self):
        selected = select_candidates(
            [candidate(), candidate("Notícia sem relação", 0)],
            {"https://example.org/fonte"},
        )
        self.assertEqual(selected, [])

    def test_prompt_enforces_plain_short_original_article(self):
        prompt = build_prompt([candidate()])
        self.assertIn("pessoas comuns", prompt)
        self.assertIn("nunca ultrapasse 700", prompt)
        self.assertIn("Não copie frases extensas", prompt)
        self.assertIn("O que fazer agora", prompt)
        self.assertIn("impacto concreto no bolso", prompt)

    def test_validator_rejects_legalese_and_unlisted_source(self):
        body = " ".join(["informação útil"] * 180)
        draft = {
            "title": "Está endividado? Veja por onde começar hoje",
            "meta_description": "Orientação simples para organizar dívidas.",
            "body_html": f"<h2>O que isso significa para você</h2><p>Data venia {body}</p><h2>O que fazer agora</h2><p>{body}</p>",
            "keywords": ["dívidas"],
            "sources": ["https://outra-fonte.example"],
            "editorial_note": "Rascunho",
        }
        failures = validate_draft(draft, [candidate()])
        self.assertTrue(any("expressão proibida" in failure for failure in failures))
        self.assertTrue(any("fonte não presente" in failure for failure in failures))


if __name__ == "__main__":
    unittest.main()
