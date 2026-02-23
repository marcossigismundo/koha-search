# Busca Koha

Plugin WordPress para integrar busca com o Koha ILS (Integrated Library System) - Rede de Bibliotecas do Ibram.

**Versao:** 4.0.0
**Autor:** CTINF / Ibram
**Licenca:** GPL v2 or later
**Requisitos:** WordPress 5.9+, PHP 7.4+, OpenSSL

---

## Funcionalidades

- Caixa de busca que redireciona para o OPAC do Koha
- Busca por autoridades
- Filtro por biblioteca
- Disponivel como shortcode e widget
- Cache de resultados via WordPress Transients
- Rate limiting por IP
- Interface moderna e responsiva
- Acessibilidade (WCAG 2.1 AA)
- Internacionalizacao (i18n) com text domain `busca-koha`
- Admin completo com 5 abas (Conexao, Bibliotecas, Busca, Aparencia, Ajuda)
- Teste de conexao passo-a-passo
- Importacao de bibliotecas da API Koha
- Credenciais criptografadas (AES-256-CBC)
- REST API propria para integracao externa
- CSS customizavel e cor tematica

## Instalacao

1. Copie a pasta `koha-search` para `/wp-content/plugins/`
2. Ative o plugin no menu **Plugins** do WordPress
3. Acesse **Busca Koha** no menu lateral para configurar
4. Configure a URL do OPAC na aba **Conexao**
5. Configure as bibliotecas na aba **Bibliotecas**
6. Use o shortcode `[busca_koha]` ou o widget **Busca Koha** em qualquer pagina

## Shortcodes

### `[busca_koha]`

Exibe o formulario de busca no acervo. A busca redireciona para o Koha.

| Parametro | Valores | Descricao |
|---|---|---|
| `mostrar_titulo` | `true` / `false` | Exibe/oculta titulo |
| `mostrar_bibliotecas` | `true` / `false` | Exibe/oculta filtro bibliotecas |
| `show_title` | `true` / `false` | Alias para mostrar_titulo |
| `show_libraries` | `true` / `false` | Alias para mostrar_bibliotecas |

### `[koha_iframe]`

Incorpora o OPAC do Koha em um iframe.

| Parametro | Valores | Descricao |
|---|---|---|
| `height` | numero | Altura do iframe em pixels (padrao: 700) |

## Widget

O plugin registra o widget **Busca Koha** que pode ser adicionado em qualquer area de widgets do tema. Opcoes:

- Titulo do widget
- Exibir/ocultar "Pesquise em nosso acervo"
- Exibir/ocultar filtro de bibliotecas

## API REST

| Metodo | Endpoint | Permissao |
|---|---|---|
| `POST` | `/wp-json/busca-koha/v1/search` | Publica (rate-limited) |
| `GET` | `/wp-json/busca-koha/v1/libraries` | Publica |
| `POST` | `/wp-json/busca-koha/v1/libraries/import` | Administrador |
| `POST` | `/wp-json/busca-koha/v1/admin/test-connection` | Administrador |

## Configuracao do Koha

O plugin redireciona a busca para o OPAC do Koha. Para funcionar:

1. **URL do OPAC** configurada (ex: `https://bibliotecas-koha.museus.gov.br/cgi-bin/koha`)
2. **Importacao de bibliotecas (opcional):** requer API REST habilitada (Koha 17.11+)

Para funcionalidades avancadas (busca server-side via API):

1. **API REST habilitada**
2. **Autenticacao** configurada:
   - OAuth2: habilitar `RESTOAuth2ClientCredentials` nas preferencias do sistema
   - Basic Auth: habilitar `RESTBasicAuth` (requer Plack)

Documentacao: https://api.koha-community.org/

## Seguranca

- Nonce verification em todos os forms e endpoints admin
- Capability checks (`manage_options`) em operacoes administrativas
- Input sanitization em todos os campos
- Output escaping em todos os templates
- Credenciais criptografadas com AES-256-CBC
- Rate limiting configuravel por IP
- Protecao contra acesso direto a arquivos PHP
- SSL/TLS enforced nas conexoes com Koha

## Estrutura

```
koha-search/
├── koha-search-otimizado.php    # Bootstrap
├── uninstall.php                # Limpeza ao desinstalar
├── includes/
│   ├── Core/                    # Plugin, Activator, Deactivator, Encryption, Migrator
│   ├── Admin/                   # Admin_Controller, Settings_Registry, views/
│   ├── API/                     # REST controllers (Search, Libraries, Connection)
│   ├── Services/                # Koha_Client, Search_Service, Library_Service, Cache_Service
│   └── Frontend/                # Asset_Loader, Shortcode_Handler, Search_Widget
├── templates/                   # search-form.php
├── assets/css/                  # public.css, admin.css
├── assets/js/                   # public.js, admin.js
└── languages/                   # busca-koha.pot
```
