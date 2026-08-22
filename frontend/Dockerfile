FROM node:20-alpine

WORKDIR /app

# Copier les fichiers de dépendances et installer
COPY package*.json ./
RUN npm install

# Copier le reste du code
COPY . .

# Exposer le port 3000 (voir docker-compose.yml)
EXPOSE 3000

# Lancer Vite en mode dev, accessible depuis l'extérieur du container, sur le port 3000
CMD ["npm", "run", "dev", "--", "--host", "0.0.0.0", "--port", "3000"]