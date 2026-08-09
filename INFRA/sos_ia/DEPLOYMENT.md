# Implantação segura do piloto

## Pré-requisito de acesso

Registrar localmente um alias SSH para a nova VPS e autorizar uma chave individual. Não registrar senha, token do Brevo ou senha do PostgreSQL no workspace.

## Instalação privada

1. Confirmar na VPS o hostname `ia-sosconsumidor`, espaço em disco, memória e estado do Docker.
2. Criar `/opt/sos-ia/{api,compose,data/postgres,data/ollama}` com proprietário administrativo dedicado.
3. Transferir `api/` e `compose/docker-compose.yml`.
4. Criar `compose/.env` a partir do exemplo diretamente na VPS e restringir sua permissão para `600`.
5. Validar `docker compose config` sem imprimir o resultado completo, pois ele contém segredos resolvidos.
6. Construir e iniciar os containers mantendo `ENABLE_QUESTIONS=false`.
7. Baixar o modelo com `docker exec sos-ia-ollama ollama pull qwen3:4b-instruct`.
8. Confirmar `curl --fail http://127.0.0.1:8000/health` e executar `scripts/smoke_test.sh`.

O identificador `qwen3:4b-instruct` ocupa aproximadamente 2,5 GB. Antes do download, confirmar pelo menos 6 GB livres para modelo, imagens e margem operacional.

## Gate antes de habilitar respostas

- Fluxo de cadastro, confirmação e logout testado.
- Termos e política publicados com a mesma versão registrada em `TERMS_VERSION`.
- Conjunto inicial de perguntas jurídicas revisado por responsável editorial.
- Testes de CPF, cartão, senha, prompt injection e promessa de resultado aprovados.
- Latência e uso de memória medidos na VPS.
- Backup do PostgreSQL e teste de restauração concluídos.

Somente depois desses itens alterar `ENABLE_QUESTIONS=true`, reiniciar a API e realizar um piloto com poucos usuários convidados.

## Publicação posterior

Criar o DNS de `forum.sosconsumidor.com.br` apenas quando o proxy reverso estiver pronto. O proxy deve oferecer HTTPS, limite por IP, tamanho máximo de requisição e cabeçalhos de segurança. PostgreSQL e Ollama nunca devem receber portas públicas.

## Retorno seguro

Se a API falhar, definir `ENABLE_QUESTIONS=false` e reiniciar apenas o container da API. Os dados persistentes permanecem em `/opt/sos-ia/data`. Não apagar volumes durante rollback.
