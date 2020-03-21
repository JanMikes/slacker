# Slacker
Tool to automate activity check during home office :-).

It periodically (every 1-5 minutes) connects to Microsoft Exchange server, looks for **unread** emails with given subject sent by given sender, looks for activity check link and clicks it (using headless chrome in background). 

## Usage

Script is configured via environment variables:

- `SLACKER_EXCHANGE_EMAIL` - your e-mail
- `SLACKER_EXCHANGE_USER` -  username
- `SLACKER_EXCHANGE_PASSWORD` - password
- `SLACKER_MESSAGE_SUBJECT` - subject the script will be looking for
- `SLACKER_MESSAGE_SENDER` - message sender

All these environment variables are **REQUIRED**

### Manually via PHP
```
SLACKER_EXCHANGE_EMAIL="john@doe.com" SLACKER_EXCHANGE_USER="john" SLACKER_EXCHANGE_PASSWORD="foobar" SLACKER_MESSAGE_SUBJECT="Activity check" SLACKER_MESSAGE_SENDER="boss@acme.com" php bin/console check-mail
```

If you want to save your credentials, you can create `.env.local` (you can use `.env` file as template), then you can use just `bin/console check-mail`

### Run via Docker (preferred)

```docker
docker run \
    -it \
    --init \
    -e SLACKER_EXCHANGE_EMAIL="john@doe.com" \
    -e SLACKER_EXCHANGE_USER="john" \
    -e SLACKER_EXCHANGE_PASSWORD="foobar" \
    -e SLACKER_MESSAGE_SUBJECT="Activity check" \
    -e SLACKER_MESSAGE_SENDER="boss@acme.com" \
    lexinek/slacker
```
