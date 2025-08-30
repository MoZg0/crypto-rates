# Crypto Rates ![PHP Version](https://img.shields.io/badge/PHP-8.4-blue) ![Symfony Version](https://img.shields.io/badge/Symfony-7.3-black)

A lightweight service for fetching and serving cryptocurrency rates with periodic updates, designed for scalability and observability.

## Features

- Periodic updates of cryptocurrency rates from Binance API
- API endpoints for retrieving crypto rates
- Containerized with Docker and Docker Compose for easy management
- Built with Symfony 7.3 and PHP 8.4
- Uses Roadrunner for efficient non-FPM PHP application runtime

## Installation

1. Clone the repository:
   ```bash
   git clone https://github.com/MoZg0/crypto-rates.git
   cd crypto-rates
   ```

2. Start the local environment with Docker Compose:
   ```bash
   make up
   ```

3. Use Makefile targets for container management:
   - Enter the application container:
     ```bash
     make bash
     ```
   - Stop containers:
     ```bash
     make stop
     ```
   - Run all quality checks:
     ```bash
     make qa
     ```

## Usage

API endpoints are available to fetch cryptocurrency rates. Example requests can be found in the `crypto.http` files.

To fetch actual crypto rates use (inside container)
```bash
bin/console crypto:rates:fetch
```

To get started with HTTP client requests:

```bash
cp examples/http-client.env.json.example examples/http-client.env.json
```

Then use your preferred HTTP client to send requests defined in the `.http` files.

## Development

Makefile targets:

- `make up` — Start all local containers
- `make bash` — Enter the local container shell
- `make stop` — Stop all running containers
- `make qa` — Run all quality assurance checks including linting, static analysis, and tests

## Architecture Notes

- **Scalability:** To scale, asynchronous fetching can be implemented where commands produce messages and consumers fetch and save crypto rates.
- **Resilience:** Add proxy support and retry mechanisms with jitter for Binance API adapter to handle rate limits, server downtime and ip limits.
- **Logging:** As the project grows, more detailed business-level logs can be introduced.
- **Runtime:** Roadrunner is used as a non-FPM PHP runtime example. Note that the PHP community is moving towards FrankenPHP, though Swoole and Roadrunner were popular in high-load scenarios.
- **API Design:** Endpoints can be split into internal and public with versioning (e.g., `/internal/rates/v1/crypto/get`) for better maintainability.
- **Caching & Rate Limiting:** Response caching and rate limiting are intended to be handled at the API Gateway level, along with circuit breakers and authentication.
- **Observability:** Integration with OpenTelemetry, ElasticAPM, NewRelic, or Sentry is recommended for monitoring and tracing.
- **Data Retention:** Consider adding retention policies for stored crypto rates. Or maybe DWH? :)
- **Configuration:** Move `CRYPTO_PAIRS` from environment variables to a database-backed config with dedicated endpoints for management.
- **Kubernetes:** Optimize resource allocation and implement Horizontal Pod Autoscaling (HPA)/ScaledObject for API endpoints and consumers.
- **Code Quality:** PHPMD and Deptrac tools are not yet fully compatible with PHP 8.4. So they will not work for now

## License

Enjoy!
