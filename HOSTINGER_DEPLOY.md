# Deploy automático do tema na Hostinger

Este projeto usa uma branch de artefato para impedir que o deploy do tema altere
o WordPress, o banco de dados, os uploads ou o `wp-config.php`.

## Fluxo

1. Um push em `main` que modifica `wp-theme-locutora/**` executa o workflow
   `Prepare Hostinger theme deployment`.
2. O workflow valida os arquivos PHP e publica somente o conteúdo da pasta do
   tema na branch `hostinger-theme`.
3. A integração Git da Hostinger acompanha `hostinger-theme` e atualiza apenas
   a pasta do tema instalada no WordPress.

## Configuração única no hPanel

Antes de conectar o repositório, faça um backup da pasta atual do tema pelo
Gerenciador de Arquivos da Hostinger.

1. Abra **Websites → Dashboard → Avançado → Git**.
2. Clique em **Continue with GitHub** e autorize o repositório
   `uchidate/locutora_2026`.
3. Selecione a branch `hostinger-theme`.
4. Configure o diretório de destino como:

   `public_html/wp-content/themes/wp-theme-locutora`

   Se o WordPress desse site estiver instalado em outra raiz, mantenha o sufixo
   `/wp-content/themes/wp-theme-locutora` e ajuste somente o início do caminho.
5. Confirme o primeiro deploy e habilite **Auto-deployment**.
6. Verifique no histórico da Hostinger se o commit da branch
   `hostinger-theme` foi aplicado.

## Limites de segurança

- Nunca configure este repositório para publicar diretamente em `public_html`.
- A branch `hostinger-theme` é gerada automaticamente e não deve ser editada.
- Conteúdo e configurações do WordPress continuam no banco de dados e não fazem
  parte deste deploy.
- O workflow não usa senhas de FTP, SFTP ou do hPanel.
