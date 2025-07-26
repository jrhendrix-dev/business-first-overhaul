# frontend/Dockerfile
# frontend/Dockerfile
FROM node:20.19-alpine

# Set working directory
WORKDIR /app

# Copy package.json and package-lock.json
COPY package*.json ./

# Install dependencies
RUN npm install

# Copy the rest of the app
COPY . .

# Expose port used by Angular dev server
EXPOSE 4200

# Default command
CMD ["npm", "run", "start"]
