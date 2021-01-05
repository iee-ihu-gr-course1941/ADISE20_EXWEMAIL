/* globals Typed */
/* eslint-disable-next-line no-unused-expressions */
({ self }) => {
  const listOfGames = self.querySelector('div.ul')

  fetch('actions/game/list.php')
    .then(response => response.json())
    .then(data =>
      data.forEach(function (game) {
        const entry = document.createElement('div')
        entry.className = 'li'

        const codeEl = document.createElement('span')
        codeEl.innerText = game.code
        entry.appendChild(codeEl)

        const playersEl = document.createElement('span')
        playersEl.innerHTML = `${game.players.length}/${game.state.seats}`
        playersEl.style.float = 'right'
        entry.appendChild(playersEl)

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
        listOfGames.appendChild(entry)
      })
    )

  // typing animation
  /* eslint-disable-next-line no-new */
  new Typed('.typing', {
    strings: ['Set down doubles early.', 'Set down your heavier tiles early.', 'Hold on to a variety of suits.', 'Note your opponents weak suits.', 'Work out your opponent\'s hand.','Be Aware of the Board Count', 'Don’t be Afraid to Lower the Board Count', 'Use Blank Tiles to Your Advantage'],
    typeSpeed: 60,
    backSpeed: 70,
    loop: true,
    shuffle: true,
    backDelay: 2000
  })
}
