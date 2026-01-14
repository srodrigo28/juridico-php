
> ## criar arquivo docker-compose.yml
```
version: "3.8"

services:
  mysql:
    image: mysql:8.0
    container_name: mysql8
    ports:
      - "3306:3306"
    environment:
      MYSQL_ALLOW_EMPTY_PASSWORD: "yes"
      MYSQL_DATABASE: adv
    volumes:
      - "c:/xampp/mysql/data:/var/lib/mysql"
    restart: unless-stopped
```

> ### cria usando o script
docker compose up -d

> ### Ver containers que estão rodando
```
docker -ps
```

> ### Se quiser ver todos (inclusive parados):
```
docker ps -a
```

> ### Parar seu MySQL ( PARA MANUTENÇAO LEMBRAR DE PARAR )
```
docker stop mysql8
```

> ### Iniciar o MySQL novamente
```
docker start mysql8
```

> ## para remover
```
docker stop mysql8
```

```
docker rm -f mysql8
```

> * verifica se removeu
docker ps -a


## Caso precise fazer backup do banco local que usava no computador
> * com servidor parado.
```
docker stop mysql8
```

> * 1️⃣ Parar e remover o container
```
docker compose down
```

> * 2️⃣ Subir de novo
```
docker compose up -d
```

> ### Iniciar o MySQL novamente
```
docker start mysql8
```