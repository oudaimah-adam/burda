# Set up the project
1. After cloning the repo, please navigage to `devops` folder.
2. There you will see a `.env.dist` file, please copy it to the source folder of the project and rename it to `.env`
3. Feel free to modify the database access values\
   Please notice that changing `POSTGRES_PORT` and the port of `POSTGRES_PORT` will require you to mofify the port mapping inside the `docker-compose.yml` file.
5. Run the following command: `docker-compose up -d`
6. After that you need to exectue the following command in order to booup the symnofy application:\
   `docker-compose exec --user www-data php-fpm sh -c "cd /var/www; composer service:install-local;"`

Now the project is ready.
To start hitting the endpoints of the project, you need to use this base URL: `localhost:8089`\
I changed it from `80` to `8089` because the port `80` was already in-use on my machine.

# Available EndPoints:
1. POST -> localhost:8089/author\
   This is to create a new aothor.
   The json paload may look like this:\
   `{
    "name": "TestName",
    "biography": "TestBio",
    "birthdate": "1995-01-01"
    }`
2. GET -> localhost:8089/author/{authorID}
3. PUT -> localhost:8089/author/{authorID}\
   To update a certain author\
   You can provide the same payload as in the create endpoint
4. DELETE -> localhost:8089/author/{authorID}

To hit the BlogPost endpoints replace the `author` with `post` from the pervious URLs.\
Plus the payload for create/update is:\
`{
    "title": "TestTitle",
    "content": "TestContent",
    "author_id": {author_id}
 }`\
 And for the update endpoint, you don't have to provide a `author_id` in the body request.
