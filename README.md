Page Analyzer – a site that analyzes specified pages for SEO suitability similar to PageSpeed Insights.

Анализатор страниц – сайт, который анализирует указанные страницы на SEO пригодность по аналогии с PageSpeed Insights.

### Hexlet tests and linter status:
[![Actions Status](https://github.com/RasmuS2024/Page-Analyzer/actions/workflows/hexlet-check.yml/badge.svg)](https://github.com/RasmuS2024/Page-Analyzer/actions)

### Codeclimate maintainability status:
<a href="https://codeclimate.com/github/RasmuS2024/php-project-9/maintainability"><img src="https://api.codeclimate.com/v1/badges/17aa4a2260d5a8f4e86a/maintainability" /></a>

### Project on render.com:
https://php-project-9-fiaa.onrender.com

### Prerequisites
* Linux, WSL
* PHP >= 8.3.6
* PostgreSQL >= 16.8
* Composer
* Make
* Git

### Setup
```bash
git clone https://github.com/RasmuS2024/Page-Analyzer.git
cd Page-Analyzer
make install
```

### Start and use
You must define the DATABASE_URL environment variable according to the parameters of your PostgreSQL database ("user", "password" and "db_name").
```bash
export DATABASE_URL='postgresql://user:password@localhost:5432/db_name'
make start
```
At http://localhost:8000 the Page Analyzer will start.
The IP address and port are configured in the Makefile