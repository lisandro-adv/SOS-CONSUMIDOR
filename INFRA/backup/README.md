# Backups gratuitos das VPS

## VPS do site — 187.77.48.16

O Hestia cria diariamente um backup integral do usuário `user` às 05:10 UTC
(02:10 em Brasília). Uma unidade `systemd` valida o catálogo TAR e o SHA-256
às 06:00 UTC, mantém sete cópias por hard link em
`/backup/sos-backup-retention/daily` e alerta se restarem menos de 12 GiB.

Às 04:00 no Mac, o `launchd` baixa por SSH a cópia validada para
`BACKUPS/VPS_AUTOMATICOS/SITE`. A pasta fica no Dropbox e mantém os sete dias
mais recentes, além de quatro domingos. O job também roda no login e não baixa
novamente um arquivo que já esteja íntegro.

O backup Hestia inclui sites, bancos MySQL, e-mail, DNS, crons e diretórios do
usuário. A restauração integral continua sendo feita pelo Hestia; nunca execute
restauração sobre produção sem criar um ponto de segurança e testar o arquivo.

## VPS de IA — 145.223.95.156

A política da KVM 4 será instalada após o cadastramento da chave SSH. Ela terá
dump consistente dos bancos, manifesto dos containers, cópia das configurações,
volumes e dados persistentes, sem incluir caches de modelos que possam ser
rebaixados. A seleção final depende do inventário real desse servidor.
