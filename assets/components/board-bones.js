/* eslint-disable-next-line no-unused-expressions */
({ self, globals: { session, parseComponent, renderPage } }) => {
  const playerReady = self.querySelector('.player-ready')
  const playerHand = self.querySelector('.player-hand')
  const gameBoard = self.querySelector('.game-board')
  const yourTurn = self.querySelector('.your-turn')
  const leave = self.querySelector('.leave')

  const interval = setInterval(() => {
    fetch('actions/game/status.php')
      .then(async res => {
        if (res.status !== 200) {
          clearInterval(interval)
          alert('Game has ended')
          renderPage('lobby')
        }

        const status = await res.json()

        const player = status.players.find(p => p.username === session.user.username)
        if (player.ready) {
          playerReady.style.display = 'none'
          yourTurn.style.display = 'block'
        } else {
          yourTurn.style.display = 'none'
        }

        if (status.game.status === 'waitingForPlayers') {
          yourTurn.querySelector('h3').innerText = 'Waiting for other players ...'
        } else if (status.turn === player.id) {
          yourTurn.querySelector('h3').innerText = 'It\'s your turn'
        } else if (status.game.status === 'running') {
          const currentPlayer = status.players.find(p => p.id === status.turn).username
          yourTurn.querySelector('h3').innerText = `It's ${currentPlayer}'s turn`
        } else {
          yourTurn.style.display = 'none'
        }

        const suggestions = JSON.stringify(status.suggestions)
        while (playerHand.firstChild) {
          playerHand.firstChild.remove()
        }
        status.hand.forEach(async (bone, index) => {
          const { component } = await parseComponent('bone', false, bone)

          const body = new URLSearchParams()
          body.append('bone', index)

          if (
            !status.board.length ||
            bone.includes(status.board[0][0])
          ) {
            body.append('position', 0)
          } else {
            body.append('position', 1)
          }

          if (status.board.length) {
            const boneStr = JSON.stringify(bone)
            if (!suggestions.includes(boneStr)) {
              component.querySelectorAll('.bone')
                .forEach(side => { side.style.opacity = 0.3 })
            }
          }

          component.onclick = () => fetch(
            'actions/movements/place.php',
            { method: 'POST', body }
          )
            .then(res => res.json())
            .then(data => data.message && alert(data.message))
          playerHand.appendChild(component)
        })

        while (gameBoard.firstChild) {
          gameBoard.firstChild.remove()
        }
        status.board.forEach(async bone => {
          const { component } = await parseComponent('bone', false, bone)
          gameBoard.appendChild(component)
        })
      })
  }, 2000)

  playerReady.onclick = () => fetch('actions/game/ready.php', { method: 'POST' })
  leave.onclick = () => fetch('actions/game/leave.php', { method: 'POST' })
}
