# AI Descriptions Configuration

This guide explains how to configure and use AI-generated descriptions for your releases.

## Overview

The AI Description feature generates human-readable descriptions of changes that explain:
- **What changed** - Clear summary of modifications
- **Code impacts** - Technical changes for developers
- **System impacts** - Effects on performance, security, architecture
- **Client impacts** - What changes for end users

## Quick Setup

### 1. Enable AI Descriptions

```bash
# In your .env file
RELEASE_MANAGER_AI_DESCRIPTION_ENABLED=true
RELEASE_MANAGER_AI_PROVIDER=openai
```

### 2. Configure Your AI Provider

Choose one of the supported providers:

#### OpenAI (Recommended)
```bash
OPENAI_API_KEY=your_openai_api_key
RELEASE_MANAGER_OPENAI_MODEL=gpt-3.5-turbo
RELEASE_MANAGER_OPENAI_MAX_TOKENS=1000
```

#### Anthropic (Claude)
```bash
ANTHROPIC_API_KEY=your_anthropic_api_key
RELEASE_MANAGER_ANTHROPIC_MODEL=claude-3-sonnet-20240229
RELEASE_MANAGER_ANTHROPIC_MAX_TOKENS=1000
```

#### Ollama (Local)
```bash
RELEASE_MANAGER_AI_PROVIDER=ollama
RELEASE_MANAGER_OLLAMA_BASE_URL=http://localhost:11434
RELEASE_MANAGER_OLLAMA_MODEL=llama2
```

### 3. Use AI Descriptions

```bash
php artisan release --ai-description
```

## Configuration Options

### Provider Selection

```bash
# OpenAI (default)
RELEASE_MANAGER_AI_PROVIDER=openai

# Anthropic Claude
RELEASE_MANAGER_AI_PROVIDER=anthropic

# Ollama (local)
RELEASE_MANAGER_AI_PROVIDER=ollama
```

### Template Selection

Choose between two description templates:

#### Default Template (300 words max)
```bash
RELEASE_MANAGER_AI_TEMPLATE=default
```

**Output format:**
- Cosa è Cambiato
- Impatto Lato Codice
- Impatto Lato Sistema
- Impatto Lato Cliente

#### Detailed Template (500 words max)
```bash
RELEASE_MANAGER_AI_TEMPLATE=detailed
```

**Output format:**
- Panoramica
- Modifiche Tecniche
- Impatto Sistema
- Impatto Utente
- Considerazioni

### OpenAI Configuration

```bash
# Required
OPENAI_API_KEY=sk-your-api-key-here

# Optional
RELEASE_MANAGER_OPENAI_MODEL=gpt-3.5-turbo  # or gpt-4, gpt-4-turbo
RELEASE_MANAGER_OPENAI_MAX_TOKENS=1000
```

**Supported Models:**
- `gpt-3.5-turbo` (recommended, cost-effective)
- `gpt-4` (higher quality, more expensive)
- `gpt-4-turbo` (latest, best quality)

### Anthropic Configuration

```bash
# Required
ANTHROPIC_API_KEY=sk-ant-your-api-key-here

# Optional
RELEASE_MANAGER_ANTHROPIC_MODEL=claude-3-sonnet-20240229
RELEASE_MANAGER_ANTHROPIC_MAX_TOKENS=1000
```

**Supported Models:**
- `claude-3-sonnet-20240229` (recommended)
- `claude-3-opus-20240229` (highest quality)
- `claude-3-haiku-20240307` (fastest, cheapest)

### Ollama Configuration

```bash
# Required
RELEASE_MANAGER_AI_PROVIDER=ollama

# Optional
RELEASE_MANAGER_OLLAMA_BASE_URL=http://localhost:11434
RELEASE_MANAGER_OLLAMA_MODEL=llama2
```

**Supported Models:**
- `llama2` (default)
- `llama2:13b` (better quality)
- `codellama` (code-focused)
- `mistral` (alternative)

## Usage Examples

### Basic Usage

```bash
# Generate AI description with release
php artisan release --ai-description

# Force specific version with AI description
php artisan release --minor --ai-description

# Dry run with AI description
php artisan release --dry-run --ai-description
```

### Example Output

```
🤖 Generating AI description...
✓ AI description generated

--- AI Description ---
## Cosa è Cambiato

Questa release introduce un sistema di autenticazione a due fattori (2FA) per migliorare la sicurezza degli account utente, insieme a ottimizzazioni delle performance per le query del database.

## Impatto Lato Codice

- Nuovo modello `TwoFactorAuth` per gestire i codici 2FA
- Modifiche al controller `AuthController` per supportare la verifica 2FA
- Aggiunta di middleware per proteggere le rotte sensibili
- Nuove migrazioni per le tabelle 2FA

## Impatto Lato Sistema

- Miglioramento della sicurezza con autenticazione a due fattori
- Ottimizzazione delle query N+1 nel dashboard utenti
- Riduzione del tempo di caricamento del 40%
- Aggiunta di logging per eventi di sicurezza

## Impatto Lato Cliente

- Gli utenti potranno abilitare 2FA nelle impostazioni account
- Dashboard più veloce e reattivo
- Maggiore sicurezza per i dati personali
- Notifiche email per nuovi accessi sospetti
--- End AI Description ---
```

### Detailed Template Output

```
--- AI Description ---
## Panoramica

Questa release major (v2.0.0) rappresenta un significativo aggiornamento dell'architettura del sistema, introducendo un nuovo sistema di microservizi e una completa ristrutturazione dell'API.

## Modifiche Tecniche

- Implementazione di architettura a microservizi
- Nuova API REST con versioning v2
- Migrazione da MySQL a PostgreSQL
- Implementazione di Redis per caching distribuito
- Aggiunta di containerizzazione con Docker

## Impatto Sistema

- Miglioramento della scalabilità orizzontale
- Riduzione del tempo di risposta del 60%
- Aumento della disponibilità del 99.9%
- Implementazione di monitoring avanzato
- Backup automatizzati e disaster recovery

## Impatto Utente

- Interfaccia utente completamente ridisegnata
- Nuove funzionalità di dashboard in tempo reale
- Migliori performance di caricamento
- Supporto per dispositivi mobili ottimizzato
- Nuovo sistema di notifiche push

## Considerazioni

- **Breaking Changes**: L'API v1 sarà deprecata in 6 mesi
- **Migrazione**: Piano di migrazione graduale per utenti esistenti
- **Training**: Sessione di formazione per il team di supporto
- **Monitoring**: Implementazione di alerting per le nuove metriche
--- End AI Description ---
```

## Best Practices

### 1. Choose the Right Provider

- **OpenAI**: Best for most use cases, good balance of quality and cost
- **Anthropic**: Excellent for complex technical descriptions
- **Ollama**: Perfect for privacy-sensitive environments

### 2. Select Appropriate Template

- **Default**: Use for regular releases, stakeholder updates
- **Detailed**: Use for major releases, technical documentation

### 3. Optimize Token Usage

```bash
# For shorter descriptions
RELEASE_MANAGER_OPENAI_MAX_TOKENS=500

# For longer, detailed descriptions
RELEASE_MANAGER_OPENAI_MAX_TOKENS=1500
```

### 4. Cost Management

- Use `gpt-3.5-turbo` for regular releases
- Use `gpt-4` only for major releases
- Consider Ollama for high-volume usage

## Troubleshooting

### Common Issues

#### AI Description Not Generated

1. Check if AI descriptions are enabled:
   ```bash
   RELEASE_MANAGER_AI_DESCRIPTION_ENABLED=true
   ```

2. Verify API key configuration:
   ```bash
   # For OpenAI
   OPENAI_API_KEY=your_key_here
   
   # For Anthropic
   ANTHROPIC_API_KEY=your_key_here
   ```

3. Check provider configuration:
   ```bash
   RELEASE_MANAGER_AI_PROVIDER=openai
   ```

#### API Errors

1. **Invalid API Key**: Verify your API key is correct and active
2. **Rate Limiting**: Wait a few minutes and try again
3. **Model Not Available**: Check if the model is available in your region
4. **Insufficient Credits**: Add credits to your AI provider account

#### Poor Quality Descriptions

1. **Use Detailed Template**: Switch to detailed template for complex changes
2. **Increase Max Tokens**: Allow more tokens for longer descriptions
3. **Better Model**: Upgrade to a more capable model (gpt-4, claude-3-opus)

#### Ollama Issues

1. **Service Not Running**: Start Ollama service
   ```bash
   ollama serve
   ```

2. **Model Not Installed**: Install the required model
   ```bash
   ollama pull llama2
   ```

3. **Wrong Base URL**: Check the base URL configuration
   ```bash
   RELEASE_MANAGER_OLLAMA_BASE_URL=http://localhost:11434
   ```

### Debug Mode

Enable debug logging to troubleshoot issues:

```bash
LOG_LEVEL=debug
```

Check Laravel logs for detailed error messages:
```bash
tail -f storage/logs/laravel.log
```

## Security Considerations

### API Key Security

- Never commit API keys to version control
- Use environment variables for all API keys
- Rotate API keys regularly
- Use least-privilege access

### Data Privacy

- AI providers may log your prompts and responses
- Consider using Ollama for sensitive projects
- Review AI provider privacy policies
- Implement data retention policies

### Cost Control

- Set up billing alerts with your AI provider
- Monitor token usage regularly
- Use appropriate models for your needs
- Consider caching for repeated requests

## Integration Examples

### With Notifications

```bash
# Generate AI description and send notifications
php artisan release --ai-description --no-notify
```

### With CI/CD

```yaml
# GitHub Actions example
- name: Create Release with AI Description
  run: |
    php artisan release --ai-description --no-confirm
    git push --follow-tags
```

### With Custom Scripts

```bash
#!/bin/bash
# Custom release script with AI description
php artisan release --ai-description

# Copy AI description to release notes
echo "AI Description generated and included in release"
```

## Advanced Configuration

### Custom Prompts

You can extend the service to use custom prompts by modifying the `AIDescriptionService` class.

### Multiple Providers

Configure fallback providers by modifying the service to try multiple providers in sequence.

### Custom Templates

Create custom description templates by extending the prompt building methods.

## Support

For issues and questions:
- Check the troubleshooting section above
- Review Laravel logs for error details
- Open an issue on GitHub
- Check AI provider documentation

## Links

- [OpenAI API Documentation](https://platform.openai.com/docs)
- [Anthropic Claude Documentation](https://docs.anthropic.com/)
- [Ollama Documentation](https://ollama.ai/docs)
- [Laravel Release Manager GitHub](https://github.com/alegiac/laravel-release-manager)
