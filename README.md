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

If you are interested in a particular version of MySQL, replace latest with the version number. Visit Docker Hub to check other tags

- List the images to check that you have downloaded them successfully:

```
docker images ls
```

**Step 2: Deploy the MySQL Container to your Docker Engine**

- Once you have the image, move on to deploying a new MySQL container with:
```
docker run --name <container_name> -e MYSQL_ROOT_PASSWORD=<my-secret-pw> -d mysql/mysql-server:latest
```
Replace `<container_name>` with the name of your choice. If you do not provide a name, Docker will generate a random one

The ***-d*** option instructs Docker to run the container as a service in the background

Replace `<my-secret-pw>` with your chosen password
  
In the command above, we used the latest version tag. This tag may differ according to the image you downloaded

- Then, check to see if the MySQL container is running: Assuming the container name specified is mysql-server

  ```
  docker ps -a
  ```
  
You should see the newly created container listed in the output. It includes container details, one being the status of this virtual environment. The status changes from health: starting to healthy, once the setup is complete.

**Step 3: Connecting to the MySQL Docker Container**

- We can either connect directly to the container running the MySQL server or use a second container as a MySQL client. Let us see what the first option looks like.

***Approach 1***

   - Connecting directly to the container running the MySQL server:
```
 docker exec -it  mysql -uroot -p 
```

   - Provide the root password when prompted. With that, you have connected the MySQL client to the server
   - Finally, change the server root password to protect your database

***Approach 2***

   - First, create a network:

```
docker network create --subnet=172.18.0.0/24 tooling_app_network 
```

Creating a custom network is not necessary because even if we do not create a network, Docker will use the default network for all the containers you run. By default, the network we created above is of DRIVER Bridge. So, also, it is the default network. You can verify this by running the docker network ls command.

But there are use cases where this is necessary. For example, if there is a requirement to control the cidr range of the containers running the entire application stack. This will be an ideal situation to create a network and specify the --subnet

For clarity’s sake, we will create a network with a subnet dedicated for our project and use it for both MySQL and the application so that they can connect.

- Run the MySQL Server container using the created network. But first, let us create an environment variable to store the root password:
```
export MYSQL_PW=<type password here>
```
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
- Run the script:
 
```
docker exec -i DB-server mysql -uroot -p$MYSQL_PW < ~/create_user.sql
```

**Step 4: Connecting to the MySQL server from a second container running the MySQL client utility**
- Run the MySQL Client Container:

```
docker run --network tooling_app_network --name DB-client -it --rm mysql mysql -h mysqlserverhost -u mysql_user -p
```
- Since it is confirmed that you can connect to your DB server from a client container, delete the client container:
```
docker rm -f DB-client
```

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
 




