<div align="center">

# 📅 Reserva de Áreas

**Sistema web para gestão de reservas de áreas institucionais**

Agende, controle conflitos de horário, gerencie usuários e imprima sua agenda — tudo em uma interface simples e rápida.

![PHP](https://img.shields.io/badge/PHP-8.1%2B-777BB4?style=flat-square&logo=php&logoColor=white)
![MySQL](https://img.shields.io/badge/MySQL-8.0-4479A1?style=flat-square&logo=mysql&logoColor=white)
![Bootstrap](https://img.shields.io/badge/Bootstrap-5.3-7952B3?style=flat-square&logo=bootstrap&logoColor=white)
![Status](https://img.shields.io/badge/status-ativo-success?style=flat-square)

</div>

---

## ✨ Funcionalidades

<table>
<tr>
<td width="50%" valign="top">

### 📆 Agenda
- Visualização por **Dia** (lista de 10 dias), **Semana** (grade por hora) e **Mês** (calendário completo)
- Verificação automática de **conflito de horário** por área
- Reservas recorrentes (semanal/mensal)
- Reservas para **múltiplos subgrupos** de uma vez
- Impressão em **A4** com layout otimizado

### 🏫 Grupos & Áreas
- Cadastro de grupos com cor de identificação
- Subgrupos vinculados a cada grupo
- Áreas com foto, capacidade e status (ativo/oculto)

</td>
<td width="50%" valign="top">

### 👥 Usuários
- Perfis **Administrador** e **Normal**
- Administrador pode criar, editar e ocultar usuários
- Registro de quem criou cada reserva
- Cancelamento com motivo e auditoria completa

### ⚙️ Configurações
- Dias e horários permitidos para reserva
- Título da página e logo personalizáveis
- Histórico de reservas canceladas com filtros

</td>
</tr>
</table>

---

## 🛠️ Tecnologias

- **PHP 8.1+** (sem frameworks, PDO + prepared statements)
- **MySQL / MariaDB**
- **Bootstrap 5** + CSS customizado
- **JavaScript** (vanilla, sem dependências externas além do Bootstrap)

---

## 🚀 Instalação local

```bash
# 1. Clone o repositório
git clone https://github.com/rfiuzap/Reserva-de-area.git
cd Reserva-de-area

# 2. Crie o banco de dados e importe o schema
mysql -u root -p -e "CREATE DATABASE reserva_area CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;"
mysql -u root -p reserva_area < database.sql

# 3. Configure as credenciais
cp includes/config.example.php includes/config.php
# edite includes/config.php com o usuário/senha do seu banco

# 4. Aponte o servidor (Apache/XAMPP) para a pasta do projeto e acesse login.php
```

> ⚠️ **Credenciais padrão:** `admin` / `admin123` — troque a senha imediatamente após o primeiro acesso, em **Usuários**.

### Publicando em hospedagem (cPanel/Hostgator)

1. Suba os arquivos para `public_html` (ou subpasta/subdomínio).
2. Crie o banco de dados e o usuário em **MySQL Databases**, e importe `database.sql` via **phpMyAdmin** (selecione o banco antes de importar).
3. Copie `includes/config.example.php` para `includes/config.php` e preencha com as credenciais reais.
4. Garanta que a pasta `uploads/` tenha permissão de escrita (755).
5. Ative o SSL gratuito (**AutoSSL**) em **SSL/TLS Status**.

---

## 📂 Estrutura do projeto

```
├── agenda.php              # Visão "Dia" (lista de 10 dias)
├── index.php               # Visões "Semana" e "Mês"
├── reserva_form.php         # Criação/edição de reservas
├── grupos.php, areas.php    # Cadastros
├── usuarios.php             # Gestão de usuários (admin)
├── configuracoes.php        # Configurações gerais
├── api_*.php                 # Endpoints AJAX (salvar, validar conflito, etc.)
├── includes/                 # Config, funções, layout (header/footer)
├── assets/                   # CSS e JS
└── database.sql              # Schema completo do banco
```

---

<div align="center">

Feito com dedicação para simplificar o agendamento de áreas institucionais.

</div>
