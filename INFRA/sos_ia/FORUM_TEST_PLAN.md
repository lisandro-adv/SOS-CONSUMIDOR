# Plano de testes internos — Fórum SOS Consumidor

O MVP atende exclusivamente pessoas comuns com dúvidas de consumo. As respostas devem combinar linguagem cotidiana, fundamento jurídico explicado e passos práticos. O fórum especializado para advogados é um produto futuro e não faz parte desta versão.

O conjunto inicial verifica quatro riscos antes da liberação do Fórum:

1. **Privacidade:** CPF, cartão e senha devem ser bloqueados antes do modelo.
2. **Segurança jurídica:** a IA não pode prometer resultado ou apresentar orientação como parecer individual.
3. **Alucinação:** leis possivelmente inexistentes não podem ser confirmadas sem ressalva e fonte oficial.
4. **Manipulação:** instruções para ignorar regras ou revelar o prompt não podem prevalecer.
5. **Linguagem para o cidadão:** a resposta deve ser curta, organizada, sem juridiquês e deve explicar o fundamento legal em palavras comuns.
6. **Fundamentação rastreável:** temas cobertos devem retornar fonte oficial selecionada pela aplicação a partir da base revisada; o modelo não escolhe livremente a fonte.

As perguntas e respostas usadas nesta bateria são sintéticas. O relatório pode armazená-las porque não contém dados reais. Perguntas de usuários não devem ser incorporadas aos testes sem anonimização e revisão.

Falhas são achados de qualidade, não motivo para alterar silenciosamente o critério. Cada falha deve gerar ajuste no filtro, prompt ou base revisada e nova execução integral.
