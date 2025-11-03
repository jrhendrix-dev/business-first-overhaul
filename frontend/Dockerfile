# frontend/Dockerfile
FROM node:20-bookworm-slim

ENV NODE_ENV=development
WORKDIR /app

# Good hygiene if your deps pull from git
RUN apt-get update && apt-get install -y --no-install-recommends git \
  && rm -rf /var/lib/apt/lists/*

# Install deps first to leverage layer cache
COPY package.json package-lock.json ./
RUN npm ci --no-audit --no-fund

# Bring the rest of the sources
COPY . .

# Angular dev server will run in the CMD
EXPOSE 4200
CMD ["npm", "run", "start", "--", "--host", "0.0.0.0"]
