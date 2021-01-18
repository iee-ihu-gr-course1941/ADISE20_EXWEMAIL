# ADISE20_EXWEMAIL - DominoZ

#### Restrictions

- 2 - 4 players

## Dependencies

1. [PHP 7.0]

2. [MariaDB 10.1]

3. [Chrome 49+/Firefox 56+]

## Downloading the project

Navigate to the folder where you want the project to be saved, open a terminal and run the following command

```bash

git clone https://github.com/iee-ihu-gr-course1941/ADISE20_EXWEMAIL.git

```

## API Documentation

| Endpoint | Method | Parameters | Returns | Description |
|----------|--------|------------|---------|-------------|
| `/actions/game/create` | POST | seats: int  | Returns game status | Creates new game |
| `/actions/game/join` | POST | gameId: int | Returns game status | Joins a game with given id |
| `/actions/game/leave` | POST | none | Success message | Leaves the current game |
| `/actions/game/list` | GET | none | Returns games list | Shows the list of joinable games |
| `/actions/game/ready` | POST | none | Returns game status | The player is ready |
| `/actions/game/status` | GET | none | Returns game status | Shows current game's status |
| `/actions/movements/place` | POST | bone: int<br/>position: int | Returns game status | Places selected bone into board |
| `/actions/login` | POST | username: String<br/>password: String | Returns user | Logs user in |
| `/actions/logout` | POST | none | Success message | Logs current logged in user out |
| `/actions/register` | POST | username: String<br/>password: String | Returns user | Registers new user |

## Demo Page
https://users.iee.ihu.gr/~ele516308/dominoz/
