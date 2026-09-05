FROM node:22-alpine

WORKDIR /app

COPY package*.json ./
RUN npm ci

# Copier le reste du code
COPY . .

# Exposer le port 3000 (voir docker-compose.yml)
EXPOSE 3000

# Lancer Vite en mode dev, accessible depuis l'extérieur du container, sur le port 3000
CMD ["npm", "run", "dev", "--", "--host", "0.0.0.0", "--port", "3000"]