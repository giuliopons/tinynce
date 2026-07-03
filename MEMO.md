# MEMO — Aggiungere un agente "Data Analyst" (OpenAI + MySQL)

> Analisi del 2026-06-21. Non implementato: appunti per quando lo si farà.
> Riferimento articolo: https://www.barattalo.it/coding/creare-agente-data-analyst-mysql-php-neuron-ai/

## Obiettivo

Un agente che risponde a domande in linguaggio naturale ("costi sui progetti attivi
tra gennaio e giugno 2026") generando ed eseguendo query SQL sul DB Timy.

## Decisione chiave: fonte dello schema

**NON basarsi su `install.class.php` come fonte dello schema.** È il DDL di
installazione, non lo stato reale del DB:
- Il progetto non ha sistema di migrazioni → le modifiche sono manuali → `install.class.php`
  gira solo all'install e diverge dal DB reale (drift).
- È PHP con concatenazioni e `DB_PREFIX` dinamico: non introspettabile in modo pulito.
- Mancano FK, indici, cardinalità (metadati utili all'LLM per i JOIN).

**Usare l'introspezione live** (pattern Neuron AI):
- `MySQLSchemaTool` → legge `INFORMATION_SCHEMA` dal DB reale (tabelle, colonne, FK,
  indici, UNIQUE). Sempre aggiornato, zero manutenzione.
- `MySQLSelectTool` → confina l'agente alle SELECT.
- `LoggedPDO` (o equivalente) → audit di tutte le query.
- `DataAnalystAgent extends Agent` (Neuron) → provider OpenAI + tool.

## Ruolo di install.class.php / CLAUDE.md

Restano preziosi **come contesto semantico**, non strutturale. `INFORMATION_SCHEMA`
dà la struttura ma non il significato. Da loro si deriva (una tantum) un
**glossario/business-hints** da iniettare nel system prompt:
- Convenzioni prefissi: `id_`=PK, `cd_`=FK, `dt_`=data, `nu_`=numerico, `fl_`=flag,
  `de_`=descrizione/stringa, `en_`=enum.
- Relazioni implicite: es. `cd_job → ts_job.id_job`, `cd_fornitore → ts_fornitori.id_fornitore`,
  `cd_cliente → ts_clienti.id_cliente`.
- Significato dei workflow enum: es. `ts_costi.en_status / ts_ricavi.en_status` =
  estimate → progress claim → invoice emitted → invoice payed.
- "Progetti attivi" = `ts_job.fl_attivo = 1`.

Design ottimale = **schema live (struttura) + glossario curato (semantica)**.

## Vincolo Composer: NON è un blocco

CLAUDE.md diceva "no Composer" — **errato**. Il core usa già Composer:
`config.php` fa `require src/vendor/autoload.php` (es. PHPMailer installato via Composer).
Quindi Neuron AI è integrabile sfruttando l'autoload esistente; basta aggiornare il
`composer.json` (che sta nella cartella core del framework) e rigenerare l'autoload.

## Guardrail (importanti, indipendenti da dove gira)

- **Credenziale DB dedicata READ-ONLY** (non riusare quella di `pons-settings.php`):
  il confinamento alle SELECT va garantito a livello DB, non solo dal tool.
- Limite righe + timeout sulle query.
- Logging/audit delle query eseguite.
- Filtro su `DB_PREFIX` se l'utente DB vede più installazioni sullo stesso server.
- Preferibile eseguirlo come **app/servizio separato** (come il test `D:\codice\test-neuron\test-neuron`)
  o almeno entrypoint dedicato: l'agente è read-only e isolato dal flusso di produzione.
  (Ora è una preferenza, non un obbligo, visto che Composer è disponibile.)

## Esempio di query target (riferimento)

Costi sui progetti attivi, gen–giu 2026 (con prefisso tabella nel codice reale):
```sql
SELECT j.id_job, j.de_codice, j.de_nomejob, SUM(c.nu_importo) AS totale_costi
FROM ts_costi c
INNER JOIN ts_job j ON c.cd_job = j.id_job
WHERE j.fl_attivo = 1
  AND c.dt_payment >= '2026-01-01'
  AND c.dt_payment <  '2026-07-01'
GROUP BY j.id_job, j.de_codice, j.de_nomejob
ORDER BY totale_costi DESC;
```
Nota: scegliere il campo data secondo l'intento — `dt_payment` (pagamento) vs
`dt_saved` (registrazione).

## Prossimi passi (quando si farà)

1. Definire `composer.json` core con il pacchetto Neuron AI; `composer install`.
2. Creare utente MySQL read-only + connessione dedicata.
3. Implementare `DataAnalystAgent` con `MySQLSchemaTool` + `MySQLSelectTool` + logging.
4. Scrivere il glossario business-hints (vedi sopra) come parte del system prompt.
5. Esporre l'agente come app/endpoint separato; aggiungere limiti righe/timeout.
