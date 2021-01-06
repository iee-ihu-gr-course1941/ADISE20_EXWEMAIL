/* eslint-disable-next-line no-unused-expressions */
({ self, globals: { session, parseComponent } }) => {
  const playerReady = self.querySelector('.player-ready')
  const playerHand = self.querySelector('.player-hand')
  const gameBoard = self.querySelector('.game-board')

  setInterval(() => {
    fetch('actions/game/status.php')
      .then(res => res.json())
      .then(status => {
        const player = status.players.find(p => p.username === session.user.username)
        if (player.ready) {
          playerReady.style.display = 'none'
        }

        while (playerHand.firstChild) {
          playerHand.firstChild.remove()
        }
        status.hand.forEach(async bone => {
          const { component } = await parseComponent('bone', false, bone)
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
}
