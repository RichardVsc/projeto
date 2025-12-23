# Projeto de Plataforma de Pagamentos Simplificada

Plataforma de pagamentos que permite transferências seguras entre usuários comuns e lojistas.

## 🧑‍💻 Tecnologias Utilizadas
- **Hyperf 3.1**
- **PHP 8.2**
- **PostgreSQL**
- **Redis**
- **Docker** & **Docker Compose**

## 🏗️ Arquitetura
<img width="908" height="671" alt="image" src="https://github.com/user-attachments/assets/bb657adc-fe33-4d50-952b-a732a4642492" />

Este projeto segue **Clean Architecture** com **Domain-Driven Design (DDD)**:

### Camadas:

**Domain (Regras de Negócio):**
- Aggregates: `User`, `Transfer`
- Value Objects: `Money`, `Email`, `DocumentNumber`, `HashedPassword`
- Enums: `UserType`, `TransferStatus`, `DocumentType`
- Domain Services: `AuthorizationService`, `NotificationService`

**Application (Casos de Uso):**
- Use Cases: `TransferMoneyHandler`
- DTOs: `TransferMoneyCommand`, `TransferMoneyResponse`

**Infrastructure (Implementações):**
- Repositories: `EloquentUserRepository`, `EloquentTransferRepository`
- HTTP Services: Guzzle para APIs externas
- Event Listeners: Notificações assíncronas via Redis Queue

**Presentation (API REST):**
- Controllers: `TransferController`
- Validators: `TransferControllerValidator`

---

## 🎯 Decisões Técnicas

### Por que Hyperf?
- Performance superior (Swoole/Coroutines)
- Suporte nativo a async/queue
- Ideal para sistemas financeiros de alto volume

### Concorrência (SELECT FOR UPDATE)
Implementamos **pessimistic locking** com `SELECT FOR UPDATE` para evitar race conditions:
- Locks adquiridos em ordem alfabética
- Duração mínima do lock (~10-50ms)
- Re-validação dentro da transação

### Event-Driven Architecture
Notificações são processadas de forma **assíncrona** via Redis Queue:
- Transferência não espera notificação
- Retry automático em caso de falha
- Worker separado processa jobs em background

### Imutabilidade
Todas entidades de domínio são **imutáveis**:
- Operações retornam novas instâncias
- Previne side effects
- Facilita testes
---

## 📁 Estrutura do Projeto
```
app/
├── Application/         # Casos de uso
│   ├── Listener/        # Event listeners
│   └── UseCase/         # Handlers
├── Domain/              # Regras de negócio
│   ├── Money/
│   ├── User/
│   └── Transfer/
├── Infrastructure/      # Implementações
│   ├── Persistence/
│   └── Service/
├── Controller/          # Controllers REST
├── Validators/          # Validações de input
├── Job/                 # Jobs assíncronos
└── DTO/                 # Data Transfer Objects

tests/
├── Integration/         # Testes de integração
└── Unit/                # Testes unitários
```
---
## 🔧 Componentes Principais

1. **Presentation Layer**
- **TransferController**: Recebe requisições HTTP, valida input e chama o Use Case
- **TransferControllerValidator**: Valida formato de UUIDs, amount positivo

2. **Application Layer**
- **TransferMoneyHandler (Use Case)**: Orquestra o fluxo completo da transferência
  - Prepara e valida transfer
  - Chama serviço de autorização externo
  - Executa transfer com lock pessimista
  - Dispara evento de conclusão
- **SendTransferNotificationListener**: Escuta evento `TransferCompleted` e enfileira job de notificação
- **SendTransferNotificationJob**: Processa notificação em background (worker Redis)

3. **Domain Layer**
- **User (Aggregate)**: Entidade rica com regras (canSendMoney, debitWallet, creditWallet)
- **Transfer (Aggregate)**: State machine (PENDING → AUTHORIZED → COMPLETED/FAILED)
- **Money (Value Object)**: Operações matemáticas imutáveis
- **DocumentNumber, Email, HashedPassword**: Value Objects validados
- **UserRepository & TransferRepository**: Interfaces para persistência

4. **Infrastructure Layer**
- **EloquentUserRepository & EloquentTransferRepository**: Implementam persistência com Eloquent
  - `findByIdForUpdate()`: SELECT FOR UPDATE (lock pessimista)
  - Tradução Domain Entity ↔ Database Model
- **HttpAuthorizationService**: Cliente HTTP para API externa de autorização
- **HttpNotificationService**: Cliente HTTP para API externa de notificação
- **TransactionManager**: Wrapper para transações de banco
- **Redis Queue**: Processa jobs de notificação de forma assíncrona

5. **Database**
- **PostgreSQL**: Armazena users, wallets, transfers
- **Transações ACID**: Garante atomicidade nas transferências
- **Row-level locks**: Previne race conditions

6. **External Services**
- **Authorization API** (GET): Valida se transferência pode ser autorizada (200 = OK, 403 = Negado)
- **Notification API** (POST): Envia notificação sobre transferência (204 = Sucesso)



## 🚀 Primeiros Passos

### ✅ Pré-requisitos

Certifique-se de que você tem o seguinte instalado:

- **Docker**
- **Docker Compose**

### 🛠️ Instalação

1. Clone este repositório e entre na pasta do projeto:
```bash
git clone https://github.com/RichardVsc/projeto.git && cd projeto
```

2. Suba os containers com Docker:
```bash
docker-compose up -d
```

3. Acesse o container:
```bash
docker exec -it hyperf-app bash
cd hyperf-skeleton/
```

4. Instale as dependências PHP via Composer:
```bash
composer install
```

5. Copie o arquivo `.env.example` para `.env`:

```bash
cp .env.example .env
```

6. Atualize as configurações do banco de dados dentro do seu arquivo `.env` para usar o PostgreSQL definido no `docker-compose.yml`

```bash 
DB_DRIVER=pgsql
DB_HOST=db
DB_PORT=5432
DB_DATABASE=hyperf_db
DB_USERNAME=hyperf
DB_PASSWORD=secret
DB_CHARSET=utf8
```

7. Execute as migrations e os seeders:
```bash
php bin/hyperf.php migrate:fresh --seed
```

8. Gere a documentação da API
```bash
composer docs
``` 

9. Inicie a aplicação utilizando:
```bash
composer start
```

## 📚 Endpoints Disponíveis

| Método | Endpoint          | Descrição                           |
|--------|-------------------|-------------------------------------|
| GET    | /docs             | Documentação da API (Swagger)       |
| POST   | /transfer         | Realiza uma transferência           |
---

## 🧪 Testes e Análise de Código

### 👥 Usuários de Teste

Os seeders criam 4 usuários para testes:

| Nome | Tipo | CPF/CNPJ | Saldo Inicial | UUID |
|------|------|----------|---------------|------|
| João Silva | COMMON | 147.362.320-05 | R$ 1.000,00 | `550e8400-e29b-41d4-a716-446655440001` |
| Loja ABC | MERCHANT | 30.132.630/0001-80 | R$ 500,00 | `550e8400-e29b-41d4-a716-446655440002` |
| Maria Santos | COMMON | 987.654.321-00 | R$ 200,00 | `550e8400-e29b-41d4-a716-446655440003` |
| Pedro Costa | COMMON | 111.222.333-44 | R$ 0,00 | `550e8400-e29b-41d4-a716-446655440004` |

### 📬 Testando a Rota de Transferência com cURL
Você pode testar a rota de transferência da API usando ferramentas como cURL ou Postman. Essa rota é útil para simular transferências entre usuários sem a necessidade de sessão ou CSRF, ideal para testes manuais.

1. Fazer a requisição com cURL

- Common → Merchant
   ```bash
      curl -X POST http://localhost:9502/transfer \
            -H "Content-Type: application/json" \
            -d '{
                "payer_id": "550e8400-e29b-41d4-a716-446655440001",
                "payee_id": "550e8400-e29b-41d4-a716-446655440002",
                "amount": 10000
        }'
   ```

   Resposta esperada:
   - `Status 201 Created`
   ```json
      {
        "status": "completed",
        "data": {
            "transfer_id": "uuid-gerado",
            "payer_id": "550e8400-e29b-41d4-a716-446655440001",
            "payee_id": "550e8400-e29b-41d4-a716-446655440002",
            "amount": 10000
        }
      }     
   ```
- Common → Common
   ```bash
      curl -X POST http://localhost:9502/transfer \
            -H "Content-Type: application/json" \
            -d '{
                "payer_id": "550e8400-e29b-41d4-a716-446655440001",
                "payee_id": "550e8400-e29b-41d4-a716-446655440003",
                "amount": 10000
        }'
   ```

   Resposta esperada:
   - `Status 201 Created`
   ```json
      {
        "status": "completed",
        "data": {
            "transfer_id": "uuid-gerado",
            "payer_id": "550e8400-e29b-41d4-a716-446655440001",
            "payee_id": "550e8400-e29b-41d4-a716-446655440003",
            "amount": 10000
        }
      }     
   ```

- Autorizacao negada
   ```bash
      curl -X POST http://localhost:9502/transfer \
            -H "Content-Type: application/json" \
            -d '{
                "payer_id": "550e8400-e29b-41d4-a716-446655440001",
                "payee_id": "550e8400-e29b-41d4-a716-446655440003",
                "amount": 10000
        }'
   ```

   Resposta esperada:
   - `Status 422 Unprocessable Entity`
   ```json
      {
        "status": "failed",
         "transfer_id": "uuid-gerado",
         "reason": "Authorization denied.",
      }     
   ```

- Merchant tentando enviar
   ```bash
      curl -X POST http://localhost:9502/transfer \
            -H "Content-Type: application/json" \
            -d '{
                    "payer_id": "550e8400-e29b-41d4-a716-446655440002",
                    "payee_id": "550e8400-e29b-41d4-a716-446655440001",
                    "amount": 5000
        }'
   ```

   Resposta esperada:
   - `Status 403 Forbidden`
   ```json
      {
        "status": "failed",
        "error": "This user type cannot send money."
      }     
   ```

- Saldo insuficiente
   ```bash
      curl -X POST http://localhost:9502/transfer \
            -H "Content-Type: application/json" \
            -d '{
                    "payer_id": "550e8400-e29b-41d4-a716-446655440004",
                    "payee_id": "550e8400-e29b-41d4-a716-446655440001",
                    "amount": 1000
        }'
   ```

   Resposta esperada:
   - `Status 422 Unprocessable Entity`
   ```json
      {
        "status": "failed",
        "error": "User does not have enough balance."
      }     
   ```

- User não encontrado
   ```bash
      curl -X POST http://localhost:9502/transfer \
            -H "Content-Type: application/json" \
            -d '{
                    "payer_id": "00000000-0000-0000-0000-000000000000",
                    "payee_id": "550e8400-e29b-41d4-a716-446655440001",
                    "amount": 1000
        }'
   ```

   Resposta esperada:
   - `Status 404 Not Found`
   ```json
      {
        "status": "failed",
        "error": "Payer 00000000-0000-0000-0000-000000000000 was not found."
      }     
   ```

- Validação UUID
   ```bash
      curl -X POST http://localhost:9502/transfer \
            -H "Content-Type: application/json" \
            -d '{
                    "payer_id": "abc123",
                    "payee_id": "550e8400-e29b-41d4-a716-446655440001",
                    "amount": 1000
        }'
   ```

   Resposta esperada:
   - `Status 400 Bad Request`
   ```json
      {
        "status": "failed",
        "error": [
            "The payer_id must be a valid UUID."
        ]
      }     
   ```

- Validação Amount
   ```bash
      curl -X POST http://localhost:9502/transfer \
            -H "Content-Type: application/json" \
            -d '{
                    "payer_id": "550e8400-e29b-41d4-a716-446655440001",
                    "payee_id": "550e8400-e29b-41d4-a716-446655440002",
                    "amount": 0
        }'
   ```

   Resposta esperada:
   - `Status 400 Bad Request`
   ```json
      {
        "status": "failed",
        "error": [
            "The amount must be greater than zero."
        ]
      }     
   ```

- Transferir para si mesmo
   ```bash
      curl -X POST http://localhost:9502/transfer \
            -H "Content-Type: application/json" \
            -d '{
                    "payer_id": "550e8400-e29b-41d4-a716-446655440001",
                    "payee_id": "550e8400-e29b-41d4-a716-446655440001",
                    "amount": 1000
        }'
   ```

   Resposta esperada:
   - `Status 422 Unprocessable Entity`
   ```json
      {
        "status": "failed",
        "error": "Cannot transfer to self."
      }     
   ```
   


### 📤 Rodando os Testes
Para rodar apenas os testes unitários:
```bash
composer test
```

Para rodar apenas os testes de integração:
```bash
composer integration-test
```

Para rodar todos os testes automatizados:
```bash
composer test-all
```

### 🧪 Cobertura de Testes

- **92 testes automatizados**
  - 10 testes de integração
  - 82 testes unitários


### Análise Estática de Código
Executa todas as ferramentas de análise de uma vez:
```bash
composer analyse
```

Ou utilize individualmente:
- PHPCS Fixer (formatação):
```bash
composer cs-check
```

- PHPStan (análise estática):
```bash
composer phpstan
```

- PHPMD (más práticas):
```bash
composer phpmd
```

### Correção Automática
Corrigir automaticamente os problemas de formatação:
```bash
composer cs-fix
```

## 💡 Dicas
- Se estiver com dúvidas sobre os comandos disponíveis, veja a aba "scripts" no arquivo composer.json.

- A pasta vendor/ e o arquivo composer.lock não devem ser editados manualmente.
