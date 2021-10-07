## CONTAINERIZATION WITH DOCKER 

### Docker Installation

You can learn how to install Docker Engine on your PC [here](https://docs.docker.com/engine/install/)

#### MySQL in container
Let us start assembling our application from the Database layer – we will use a pre-built MySQL database container, configure it, and make sure it is ready to receive requests from our PHP application.

**Step 1: Pull MySQL Docker Image from Docker Hub Registry**
- Start by pulling the appropriate Docker image for MySQL. You can download a specific version or opt for the latest release, as seen in the following command:

```
docker pull mysql/mysql-server:latest
```
![{95D80C28-457A-4455-9FA5-C41E6A525B17} png](https://user-images.githubusercontent.com/76074379/136104500-e91ed18f-99b5-43f2-937f-5defa5173d26.jpg)

If you are interested in a particular version of MySQL, replace latest with the version number. Visit Docker Hub to check other tags

- List the images to check that you have downloaded them successfully:

```
docker images ls
```

![{173F4432-4A1E-4903-8B95-32A354A4085F} png](https://user-images.githubusercontent.com/76074379/136104806-cec70401-120d-431a-b7e0-2db09050864a.jpg)


**Step 2: Deploy the MySQL Container to your Docker Engine**

- Once you have the image, move on to deploying a new MySQL container with:
```
docker run --name <container_name> -e MYSQL_ROOT_PASSWORD=<my-secret-pw> -d mysql/mysql-server:latest
```
Replace `<container_name>` with the name of your choice. If you do not provide a name, Docker will generate a random one

The ***-d*** option instructs Docker to run the container as a service in the background

Replace `<my-secret-pw>` with your chosen password
  
In the command above, we used the latest version tag. This tag may differ according to the image you downloaded

- Then, check to see if the MySQL container is running:

  ```
  docker ps -a
  ```
  
  ![{DB4B33D7-0399-4412-8441-15BA6C9DE002} png](https://user-images.githubusercontent.com/76074379/136105399-a3d72b73-ff66-4149-b0db-e29e2a80e227.jpg)
  
You should see the newly created container listed in the output. It includes container details, one being the status of this virtual environment. The status changes from health: starting to healthy, once the setup is complete.

**Step 3: Connecting to the MySQL Docker Container**

- We can either connect directly to the container running the MySQL server or use a second container as a MySQL client. Let us see what the first option looks like.

***Approach 1***

   - Connecting directly to the container running the MySQL server:
```
 docker exec -it <DB container name or ID> mysql -uroot -p 
```
![{5FE4CA5E-F0C8-4364-8B1C-5158CB764229} png](https://user-images.githubusercontent.com/76074379/136105829-8bc53533-01d6-434e-b637-de1d21857c29.jpg)

   - Provide the root password when prompted. With that, you have connected the MySQL client to the server
   - Finally, change the server root password to protect your database

***Approach 2***

   - First, create a network:

```
docker network create --subnet=172.18.0.0/24 tooling_app_network 
```

![{1FDF2E9B-1639-4880-9207-01514781E6A2} png](https://user-images.githubusercontent.com/76074379/136106030-0816b427-4c33-468d-a487-dcd9445a6daa.jpg)

Creating a custom network is not necessary because even if we do not create a network, Docker will use the default network for all the containers you run. By default, the network we created above is of DRIVER Bridge. So, also, it is the default network. You can verify this by running the docker network ls command.

But there are use cases where this is necessary. For example, if there is a requirement to control the cidr range of the containers running the entire application stack. This will be an ideal situation to create a network and specify the --subnet

For clarity’s sake, we will create a network with a subnet dedicated for our project and use it for both MySQL and the application so that they can connect.

- Run the MySQL Server container using the created network. But first, let us create an environment variable to store the root password:
```
export MYSQL_PW=<type password here>
```
![{F9CB36E5-72FD-40C0-8E3F-EF039B9B176F} png](https://user-images.githubusercontent.com/76074379/136106236-7ad20686-f87c-4912-8661-01dc98a9df2b.jpg)

- Then, pull the image and run the container, all in one command like below:

```
docker run --network tooling_app_network -h mysqlserverhost --name DB-server -e MYSQL_ROOT_PASSWORD=$MYSQL_PW  -d mysql/mysql-server:latest
```

- Verify the container is running:

```
docker ps -a
```

- As you already know, it is best practice not to connect to the MySQL server remotely using the root user. Therefore, we will create an SQL script that will create a user we can use to connect remotely.

Create a file and name it `create_user.sql` and add the below code in the file:
```
CREATE USER 'mysql_user'@'%' IDENTIFIED BY '1234ABC';
GRANT ALL PRIVILEGES ON * . * TO 'mysql_user'@'%';
```
![{B1943C25-42B8-4F25-9B98-05F135606C4B} png](https://user-images.githubusercontent.com/76074379/136106907-1c860de4-13e1-4743-83d1-f770600ce290.jpg)

- Run the script: 
```
docker exec -i <container name or ID> mysql -uroot -p$MYSQL_PW < ~/create_user.sql
```

![{69CFEE72-F0A1-43F5-8996-BB363B4C8C47} png](https://user-images.githubusercontent.com/76074379/136196469-c8972673-0c80-4743-b9fe-7072b4f435ad.jpg)

**Step 4: Connecting to the MySQL server from a second container running the MySQL client utility**
- Run the MySQL Client Container:

```
docker run --network tooling_app_network --name DB-client -it --rm mysql mysql -h mysqlserverhost -u mysql_user -p
```
![{1AFD3A29-2121-434F-B303-89B11C077449} png](https://user-images.githubusercontent.com/76074379/136196696-44d6d3ee-0e2e-42b1-afab-09e39fa5782b.jpg)

- Since it is confirmed that you can connect to your DB server from a client container, exit the mysql utility and press `Control+ C` to terminate the process thus removing the container( the container is not running in a detached mode since we didn't use **-d** flag ).

**Step 5: Prepare database schema**
Now you need to prepare a database schema so that the Tooling application can connect to it.

- Create a directory and name it tooling, then download the Tooling-app repository from github.
```
wget https://github.com/darey-devops/tooling.git 
```

- Unzip the file and delete the zip file
```
unzip <name-of-zip-file> && rm -f <name-of-zip-file>
```

- On your terminal, export the location of the SQL file
```
export tooling_db_schema=~/tooling/html/tooling_db_schema.sql
```
![{AD688A26-3831-4122-8177-E6CEB31E34C0} png](https://user-images.githubusercontent.com/76074379/136197683-68d45584-60a4-45d8-8e08-73099a24960a.jpg)

- You can find the `tooling_db_schema.sql` in the html folder of the downloaded repository.

Use the SQL script to create the database and prepare the schema. With the docker exec command, you can execute a command in a running container.
```
docker exec -i DB-server mysql -uroot -p$MYSQL_PW < $tooling_db_schema 
```
![{404F885C-EFE9-412F-9716-402867B35579} png](https://user-images.githubusercontent.com/76074379/136197783-a0e56483-1ec5-44ed-a288-3426e5582ce1.jpg)

- Update the db_conn.php file with connection details to the database
`
 $servername = "mysqlserverhost";
 $username = "mysql_user";
 $password = "1234ABC";
 $dbname = "toolingdb";
 `
![{77DB2E0D-A6C7-4FE7-802E-1E8183AA25FD} png](https://user-images.githubusercontent.com/76074379/136197951-b9a20ceb-26fc-4da6-8250-d009bb80e733.jpg)

**Step 6: Packaging, Building and Deploying the Application**
- A shell script named `start-apache` came with the downloaded repository. It will be referenced in a special file called `Dockerfile` and run with the `CMD` Dockerfile instruction. This will allow us to be able to map other ports to port 80 and publish them using **-p** in our command as we will see later on.

![{21F16215-A722-4C54-88EA-8510D2B08F39} png](https://user-images.githubusercontent.com/76074379/136200210-764336a3-2b26-44c1-b61a-527a72e58d47.jpg)

- Pull image from Docker registry with the code below:
```
docker pull php:7.4.24-apache-buster
```

- In the tooling directory, create a Dockerfile and paste the code below:

```
FROM php:7.4.24-apache-buster
LABEL Dare=dare@zooto.io

RUN apt-get update --fix-missing && apt-get install -y \
    default-mysql-client
    
RUN docker-php-ext-install pdo_mysql 
RUN docker-php-ext-install mysqli
COPY apache-config.conf /etc/apache2/sites-available/000-default.conf
COPY start-apache /usr/local/bin
RUN a2enmod rewrite

# Copy application source
ADD html/. /var/www
RUN chown -R www-data:www-data /var/www

CMD ["start-apache"]
```
![{4B717590-AF09-4E8E-AA2E-FC3CD63F3B6A} png](https://user-images.githubusercontent.com/76074379/136199137-9e9aacde-5de6-4813-a7c6-ba2a2006dd34.jpg)

- Ensure you are inside the folder that has the Dockerfile and build your container:
```
docker build -t tooling:0.0.1 .
```

![{EAC8C9BF-46F8-4BFE-A7E3-2893C1ECE69D} png](https://user-images.githubusercontent.com/76074379/136201150-4102f566-0802-4a23-b781-1c2490d7cfb9.jpg)


In the above command, we specify a parameter -t, so that the image can be tagged **tooling:0.0.1** - Also, you have to notice the **.** at the end. This is important as that tells Docker to locate the Dockerfile in the current directory you are running the command. Otherwise, you would need to specify the absolute path to the Dockerfile.

- Run the container:
```
docker run --network tooling_app_network --name website -d -h mysqlserverhost -p 8085:80 -it tooling:0.0.1
```
![{C03504F0-15D7-45F6-9BC8-6B37DBA74D72} png](https://user-images.githubusercontent.com/76074379/136201802-99e59589-b6e9-4605-a051-2623449772c6.jpg)

***Let us observe those flags in the command. We need to specify the --network flag so that both the Tooling app and the database can easily connect on the same virtual network we created earlier. The -p flag is used to map the container port with the host port. Within the container, apache is the webserver running and, by default, it listens on port 80. You can confirm this with the CMD ["start-apache"] section of the Dockerfile. But we cannot directly use port 80 on our host machine because it is already in use. The workaround is to use another port that is not used by the host machine. In our case, port 8085 is free, so we can map that to port 80 running in the container.***


- You can open the browser and type http://localhost:8085. The default email is test@gmail.com, the password is 12345 or you can check users' credentials stored in the toolingdb.user table.

![{21A0E16D-1852-42AE-9924-912BC09DCB92} png](https://user-images.githubusercontent.com/76074379/136201945-99638163-d8c7-4998-b036-9df99efb84eb.jpg)

- Input the login credentials

![{A671B6F5-5764-432E-A6B8-BFE407F51D66} png](https://user-images.githubusercontent.com/76074379/136202044-8a5c6aac-dec1-426d-8b4c-e4f899ae43c4.jpg)



### DEPLOYMENT USING DOCKER-COMPOSE

All we have done until now required quite a lot of effort to create an image and launch an application inside it. We should not have to always run Docker commands on the terminal to get our applications up and running. There are solutions that make it easy to write declarative code in YAML, and get all the applications and dependencies up and running with minimal effort by launching a single command.

we will refactor the Tooling app POC so that we can leverage the power of Docker Compose.

- First, install Docker Compose on your workstation. You can check the version of docker compose with this command: `docker-compose --version`
- Create a file and name it tooling.yaml
- Begin to write the Docker Compose definitions with YAML syntax. The code below represent the deployment infrastructure:

```
version: "3.9"
services:
  db:
    image: mysql/mysql-server:latest
    hostname: "${DB_HOSTNAME}"
    restart: unless-stopped
    container_name: tooling-db-server
    environment:
      MYSQL_DATABASE: "${DB_DATABASE}"
      MYSQL_USER: "${DB_USER}"
      MYSQL_PASSWORD: "${DB_PASSWORD}"
      MYSQL_ROOT_PASSWORD: "${DB_ROOT_PASSWORD}"
    ports:
      - "${DB_PORT}:3306"
    volumes:
      - db:/var/lib/mysql

  app:
    build:
      context: .
    container_name: tooling-website
    restart: unless-stopped
    volumes:
      - ~/html/.:/var/www/html
    ports:
      - "${APP_PORT}:80"
    links:
      - db
    depends_on:
      - db
  
volumes:
   db:
   ```
   
   - Create a `.env` file to reference the variables in the tooling.yml file so they can be picked up during execution.(Make sure you have dotenv installed on you workstation). Paste the below variables in the `.env` file:

```
DB_HOSTNAME=mysqlserverhost
DB_DATABASE=toolingdb
DB_USER=mysql_user
DB_PASSWORD=1234ABC
DB_ROOT_PASSWORD=1234abc
DB_PORT=3306
APP_PORT=8085
```
- You may create a `.gitignore` file and list the `.env` file in there if you do not want it added to github repository

- Run the command to start the containers
```
docker-compose -f tooling.yaml  up -d 
```

- Verify that the compose is in the running status:
```
docker compose ls
```


