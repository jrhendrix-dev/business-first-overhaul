# ---- Stage 1: build Angular
FROM node:20-alpine AS fe
WORKDIR /app
COPY ./frontend/package*.json ./
RUN npm ci
COPY ./frontend ./
# production build
RUN npm run build -- --configuration=production

# ---- Stage 2: normalize Angular dist layout
# Angular 17/18 often outputs to: /app/dist/<project>/browser
# Older setups: /app/dist (index.html at root)
FROM alpine:3.20 AS pack
RUN mkdir -p /out
COPY --from=fe /app/dist /dist
# Copy whichever layout exists into /out
RUN set -eux; \
    if [ -f /dist/index.html ]; then \
      cp -r /dist/* /out/; \
    else \
      # copy from */browser if present
      BROWSER_DIR="$(find /dist -maxdepth 2 -type d -name browser -print -quit || true)"; \
      if [ -n "$BROWSER_DIR" ]; then \
        cp -r "$BROWSER_DIR"/* /out/; \
      else \
        # fallback: copy first child dir contents
        FIRST_DIR="$(find /dist -mindepth 1 -maxdepth 1 -type d -print -quit || true)"; \
        if [ -n "$FIRST_DIR" ]; then cp -r "$FIRST_DIR"/* /out/; fi; \
      fi; \
    fi

# ---- Stage 3: Nginx serving SPA + proxy to PHP-FPM
FROM nginx:1.27-alpine
# static files
COPY --from=pack /out/ /usr/share/nginx/html/
# site config
COPY ./nginx/default.conf /etc/nginx/conf.d/default.conf
