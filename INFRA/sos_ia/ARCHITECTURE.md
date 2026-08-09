# Arquitetura do piloto — SOS Responde

## Objetivo do marco

Disponibilizar um piloto interno de perguntas educativas, autenticado por e-mail, com quota diária e inferência local. O piloto permanece fechado até que a base jurídica e os testes de segurança sejam aprovados.

## Componentes e fluxo

```text
Navegador
   |
   v
site/fórum SOS Consumidor -- HTTPS/reverse proxy --> API FastAPI (loopback)
                                                        |       |
                                                        |       +--> Ollama/Qwen (loopback)
                                                        |
                                                        +----------> PostgreSQL
                                                        |
                                                        +----------> Brevo (somente confirmação de e-mail)
```

- Somente o proxy HTTPS deverá ficar exposto publicamente.
- API, PostgreSQL e Ollama ficam sem porta pública.
- Tokens de verificação e sessão são armazenados apenas como SHA-256.
- O texto das perguntas e respostas não é persistido no piloto.
- O PostgreSQL controla a quota com atualização atômica por usuário e dia.
- O endpoint de perguntas usa `ENABLE_QUESTIONS=false` por padrão.

## Contratos principais

- `POST /v1/auth/register`: registra aceite da versão dos termos e envia link de confirmação.
- `POST /v1/auth/verify-email`: confirma o endereço e emite uma sessão bearer.
- `GET /v1/me`: valida a sessão e informa a quota do usuário.
- `POST /v1/questions`: rejeita dados sensíveis, reserva quota e consulta o modelo local.
- `POST /v1/auth/logout`: revoga a sessão atual.

## Decisões e trade-offs

- **PostgreSQL sem Redis no piloto:** reduz consumo de memória e operação. Redis pode voltar quando houver necessidade comprovada de cache ou filas.
- **Sem histórico de conversas:** reduz risco LGPD e acelera o piloto; impede retomada de conversa entre sessões.
- **Sessão opaca em vez de JWT:** permite revogação imediata e simplifica o primeiro cliente; exige consulta curta ao banco por requisição.
- **Modelo local:** evita enviar perguntas a terceiro e estabiliza custo; exige monitoramento de latência e qualidade.
- **Filtro de dados antes do modelo:** reduz exposição acidental, mas não substitui orientação visual nem revisão de privacidade.

## Próximas revisões ao crescer

Reavaliar filas, cache, histórico opt-in, alta disponibilidade, observação agregada sem conteúdo, modelos maiores e busca RAG somente depois de medir uso, latência e qualidade do piloto.
