# SOS IA — infraestrutura privada

Piloto privado do SOS Responde com Ollama/Qwen, PostgreSQL e API FastAPI.

## Estado atual

- Cadastro e confirmação de e-mail.
- Sessões revogáveis armazenadas como hash.
- Quota diária atômica.
- Filtro inicial de CPF, cartão e senhas.
- Perguntas enviadas ao Ollama sem armazenamento do conteúdo.
- Endpoint de perguntas desabilitado por padrão.

## Estrutura esperada na VPS

```text
/opt/sos-ia/
  api/
  compose/
  data/
    ollama/
    postgres/
```

API, banco e Ollama são publicados apenas em `127.0.0.1`. A exposição futura deverá ocorrer por proxy HTTPS.

## Preparação

1. Copie `compose/.env.example` para `compose/.env` na VPS.
2. Preencha os segredos diretamente no servidor; nunca os envie por chat ou salve no Git.
3. Mantenha `ENABLE_QUESTIONS=false` durante a instalação.
4. Execute a composição dentro de `/opt/sos-ia/compose`.
5. Baixe o modelo no contêiner Ollama e execute testes internos.

O endpoint de perguntas somente deve ser habilitado depois da revisão da base jurídica, dos termos e dos testes de segurança descritos em [ARCHITECTURE.md](ARCHITECTURE.md).
