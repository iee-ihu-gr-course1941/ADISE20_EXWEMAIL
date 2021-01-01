/* eslint-disable-next-line no-unused-expressions */
({ self }) => {
  fetch('actions/game/list.php')
    .then(response => response.json())
    .then(data =>
      data.forEach(function (game) {
        const entry = document.createElement('li')
        entry.innerText = game.code
        entry.addEventListener('click', click, false)
        entry.addEventListener('mouseover', mouseOver, false)
        function mouseOver () {

        }
        function click () {
          const join = new URLSearchParams()
          join.append('game-id', game.id)
          fetch('actions/game/join.php', {
            method: 'POST',
            body: join
          })
            .then(response => response.json())
            .then(data => console.log(data))
        }
        const listofgames = document.getElementById('listofgames')
        listofgames.appendChild(entry)
      })
    )
}
