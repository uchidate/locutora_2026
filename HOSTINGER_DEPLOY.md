# Deploy do tema na Hostinger preparado localmente

Este projeto usa uma branch de artefato gerada localmente para impedir que o
deploy do tema altere o WordPress, o banco de dados, os uploads, outros sites da
conta compartilhada ou o `wp-config.php`.

## Fluxo

1. As alterações são verificadas e commitadas em `main` localmente.
2. O script `scripts/publish-hostinger-theme.sh` valida o tema e envia `main` ao
   GitHub, recusando históricos divergentes.
3. Em seguida, o script gera e envia a branch `hostinger-theme`, contendo somente o conteúdo
   de `wp-theme-locutora`.
4. A integração Git da Hostinger acompanha `hostinger-theme` e atualiza apenas
   a pasta do tema instalada no WordPress.

O projeto não usa GitHub Actions para build ou deploy.

## Configuração local do WordPress

Com o ambiente Docker em execução, copie e execute o provisionamento:

```bash
docker cp scripts/configure-wordpress.php locutora_2026-wordpress-1:/tmp/configure-wordpress.php
docker exec locutora_2026-wordpress-1 php /tmp/configure-wordpress.php
```

O script pode ser executado novamente: ele preserva conteúdo editorial existente,
cria somente páginas estruturais ausentes e reaplica as opções essenciais.

## Publicação local

Depois do commit local em `main`, execute:

```bash
scripts/publish-hostinger-theme.sh
```

O script interrompe a publicação quando:

- existem mudanças locais ainda não commitadas;
- o branch atual não é `main`;
- o histórico local diverge de `origin/main`;
- os arquivos obrigatórios do tema estão ausentes;
- algum arquivo PHP ou JavaScript contém erro de sintaxe;
- o artefato contém arquivos proibidos, como `wp-config.php` ou `.env`.

Depois que a Hostinger concluir o deploy, valide versão, tema ativo e rotas:

```bash
scripts/verify-hostinger-deploy.sh
```

O domínio padrão é o ambiente temporário atual. Para outro ambiente:

```bash
HOSTINGER_SITE_URL=https://locutora.com scripts/verify-hostinger-deploy.sh
```

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
- A branch `hostinger-theme` é gerada pelo script local e não deve ser editada.
- Conteúdo e configurações do WordPress continuam no banco de dados e não fazem
  parte deste deploy.
- O processo não usa senhas de FTP, SFTP ou do hPanel.
- O domínio `*.hostingersite.com` recebe `noindex` automaticamente. Ao migrar
  para `locutora.com`, confirme em **Configurações → Leitura** que a indexação
  pública está habilitada.
