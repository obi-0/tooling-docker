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



**Step 4: Connecting to the MySQL server from a second container running the MySQL client utility**
- Run the MySQL Client Container:

```
docker run --network tooling_app_network --name DB-client -it --rm mysql mysql -h mysqlserverhost -u mysql_user -p
```
- Since it is confirmed that you can connect to your DB server from a client container, exit the mysql utility and press `Control+ C` to terminate the process thus removing the container( the container is not running in a detached mode since we didn't use **-d** flag ).

**Step 5: Prepare database schema**
Now you need to prepare a database schema so that the Tooling application can connect to it.

- Clone the Tooling-app repository from here
```
git clone https://github.com/darey-devops/tooling.git 
```

- On your terminal, export the location of the SQL file
```
export tooling_db_schema=~/tooling/html/tooling_db_schema.sql
```

- You can find the `tooling_db_schema.sql` in the html folder of the cloned repository.

Use the SQL script to create the database and prepare the schema. With the docker exec command, you can execute a command in a running container.
```
docker exec -i DB-server mysql -uroot -p$MYSQL_PW < $tooling_db_schema 
```

- Update the db_conn.php file with connection details to the database
`
 $servername = "mysqlserverhost";
 $username = "mysql_user";
 $password = "1234ABC";
 $dbname = "toolingdb";
 `

**Step 6: Packaging, Building and Shipping the Application**
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

- Ensure you are inside the folder that has the Dockerfile and build your container:
```
docker build -t tooling:0.0.1 .
```

In the above command, we specify a parameter -t, so that the image can be tagged **tooling:0.0.1** - Also, you have to notice the **.** at the end. This is important as that tells Docker to locate the Dockerfile in the current directory you are running the command. Otherwise, you would need to specify the absolute path to the Dockerfile.

- Run the container:
```
docker run --network tooling_app_network --name website -d -h mysqlserverhost -p 8085:80 -it tooling:0.0.1
```

***Let us observe those flags in the command. We need to specify the --network flag so that both the Tooling app and the database can easily connect on the same virtual network we created earlier. The -p flag is used to map the container port with the host port. Within the container, apache is the webserver running and, by default, it listens on port 80. You can confirm this with the CMD ["start-apache"] section of the Dockerfile. But we cannot directly use port 80 on our host machine because it is already in use. The workaround is to use another port that is not used by the host machine. In our case, port 8085 is free, so we can map that to port 80 running in the container.***


- You can open the browser and type http://localhost:8085. The default email is test@gmail.com, the password is 12345 or you can check users' credentials stored in the toolingdb.user table.






