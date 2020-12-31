/* eslint-disable-next-line no-unused-expressions */
({ self }) => {
  fetch('actions/game/list.php')
    .then(response => response.json())
    .then(data =>
      data.forEach(function (game) {
        const entry = document.createElement('li')
        entry.innerText = game.code
        const listofgames = document.getElementById('listofgames')
        listofgames.appendChild(entry)
      })
    )
}
