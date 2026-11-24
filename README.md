# ImageBender

ImageBender is a web app for creating AI-generated images using the Pixelforge API.

## Installation

### 1. Docker container

After cloning/download the repo. CD into the correct dirctory.

```
cd image-bender/
```

Next, create and startup the docker container, locally.

```
docker compose -f compose.dev.yaml up -d
```

If there's a port collision, adjust the `POSTGRES_PORT`, or `NGINX_PORT`

### 2. Composer setup

Use composer to setup the app with the following:

```
docker compose -f compose.dev.yml exec workspace composer setup
```

If `.env` doesn't exist yet. Create it with the following:

```
cp .env.example .env
```

Confirm the values in the `.env` file are accurate. Check the following match yours:
```
AI_IMAGE_DRIVER=mock
AI_IMAGE_BASE_URL=
AI_IMAGE_API_KEY=
AI_IMAGE_WEBHOOK_SECRET=
```

### 3. Start app

Once the composer setup is complete, start the app with the following:
```
docker compose -f compose.dev.yaml exec workspace npm run dev
```

If there's a port collision, adjust the `VITE_PORT` port. 