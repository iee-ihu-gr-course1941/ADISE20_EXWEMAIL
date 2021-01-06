/* globals Typed */
/* eslint-disable-next-line no-unused-expressions */
({ self, globals: { renderPage } }) => {
  const listOfGames = self.querySelector('div.ul')

  fetch('actions/game/list.php')
    .then(response => response.json())
    .then(data => {
      if (data.length === 0) {
        const entry = document.createElement('div')
        entry.className = 'li tooltip'

        const codeEl = document.createElement('span')
        codeEl.innerText = 'No game available, create one!'
        entry.appendChild(codeEl)
        listOfGames.appendChild(entry)
      }
      data.forEach(function (game) {
        const entry = document.createElement('div')
        entry.className = 'li tooltip'

        const extraDiv = document.createElement('div')
        entry.appendChild(extraDiv)

        const codeEl = document.createElement('span')
        codeEl.innerText = game.code
        extraDiv.appendChild(codeEl)

        const playersEl = document.createElement('span')
        playersEl.innerHTML = `${game.players.length}/${game.state.seats}`
        playersEl.style.float = 'right'
        extraDiv.appendChild(playersEl)

        // join a game event listener
        entry.addEventListener('click', join, false)

        // mouse over shows players inside the game hovered and if they are ready
        extraDiv.addEventListener('mouseover', mouseOver, false)
        function mouseOver () {
          const player = game.players
          const floatSpan = document.createElement('span')
          const floatDiv = document.createElement('div')
          floatDiv.appendChild(floatSpan)
          player.forEach(function (item, index, array) {
            const state = item.state

            if (state.ready === true) {
              floatSpan.innerHTML += item.username + ' - Ready <br/>'
            } else {
              floatSpan.innerHTML += item.username + ' - Not ready <br/>'
            }
            floatSpan.className = 'tooltiptext'
            entry.appendChild(floatDiv)
          })
        }
        // the function of joining a game
        function join () {
          const join = new URLSearchParams()
          join.append('game-id', game.id)
          fetch('actions/game/join.php', {
            method: 'POST',
            body: join
          })
            .then(response => response.json())
            .then(() => renderPage('board'))
        }
        listOfGames.appendChild(entry)
      })
    })

  // logout button function
  const logout = self.querySelector('.logout')
  logout.addEventListener('click', leave, false)
  function leave () {
    fetch('actions/logout.php')
      .then(response => response.json())
      .then(data => renderPage('login'))
  }

  // create game button function
  const create = self.querySelector('.create-game')
  create.addEventListener('click', createGame, false)
  function createGame () {
    const option = self.querySelector('.select')
    const create = new URLSearchParams()
    create.append('seats', option.value)

    return fetch('actions/game/create.php', {
      method: 'POST',
      body: create
    })
      .then(res => res.json())
      .then(() => renderPage('board'))
  }
  // typing animation
  /* eslint-disable-next-line no-new */
  new Typed('.typing', {
    strings: ['Set down doubles early.', 'Set down your heavier tiles early.', 'Hold on to a variety of suits.', 'Note your opponents weak suits.', 'Work out your opponent\'s hand.', 'Be Aware of the Board Count', 'Don’t be Afraid to Lower the Board Count', 'Use Blank Tiles to Your Advantage'],
    typeSpeed: 60,
    backSpeed: 70,
    loop: true,
    shuffle: true,
    backDelay: 2000
  })
}
