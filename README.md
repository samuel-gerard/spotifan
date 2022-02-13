# SpotiFan 🎶
This app provide your top tracks & artists, audio analize of your playlists.
Goal is to connect to songkick API to find the concerts/shows of your favorite artists around you.

# Instructions
## Configure environment file
You need to copy the `.env.dist` file to `.env` on the root of the app.
```bash
cp .env.dist .env
```
Then you need to provide your Spotify client ID & Secret in the `.env` file :
```
OAUTH_SPOTIFY_CLIENT_ID=your_client_id
OAUTH_SPOTIFY_CLIENT_SECRET=your_client_secret
```
To finish, you have to source the `.env` :
```bash
source .env
```

## Start docker containers
Once you're done, simply `cd` to your project and run `docker-compose up -d`.

## Run application
You can access your application via **`localhost:8000`**.

# Documentation Spotify
- [Authorization code flow](https://developer.spotify.com/documentation/general/guides/authorization/code-flow/)
- [All API Endpoints](https://developer.spotify.com/documentation/web-api/reference/#/)
- [Spotify project Dashboard](https://developer.spotify.com/dashboard/)

## Inspiration
- https://www.spotilyze.com
- https://obscurifymusic.com
