# Vibe 
This project is a web application for exploring music tracks, managing personal playlists, and saving favorite songs. It runs on Docker, utilizing PHP for the backend and MongoDB for the database.

## Step 1: Prepare the Dataset
Before starting the environment, you need to prepare the main database file. 
You must merge the two provided datasets (`music.csv` and `spotifytracksgenr.csv`) to generate a single JSON file named `merged_output.json`. This resulting file will serve as the main archive dataset for the application.

## Step 2: Start the Containers
Open your terminal, navigate to the main project folder (where the `docker-compose.yml` file is located), and run the following command to build and start the containers:
docker compose up -d

## Step 3: Import Data via Mongo Express

The Docker setup includes Mongo Express, a web-based GUI for managing MongoDB. You need to use it to import the dataset you created in Step 1.

1. Open your browser and go to the Mongo Express interface (default is `http://localhost:8081`).
2. Click on the `admin` database (which is the database used by the PHP scripts).
3. Create a new collection named `Spotify` (if the containers didn't create it automatically).
4. Enter the `Spotify` collection and look for the **Import** or **Upload** function in the top menu.
5. Select your `merged_output.json` file and confirm the import.
6. Instead of using a GUI(if the previous point doesn't work), you can import the dataset directly from your terminal using `mongoimport`. 
While the containers are running, open your terminal in the project folder and run the following command to inject the JSON file into the database:
docker exec -i <mongodb_container_name> mongoimport --db admin --collection Spotify --jsonArray < merged_output.json
*(Note: for the 2023 tracks, repeat the same import process for the `Spotify2023` collection).*

## Step 4: Launch the App

Once the database is populated, you are good to go.
Open your web browser and navigate to:
http://localhost
