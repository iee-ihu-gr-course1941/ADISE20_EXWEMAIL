/* eslint-disable-next-line no-unused-expressions */
({ self, globals: { parseComponent } }) => {
  const container = self.querySelector('.bones-container')
  fetch('actions/game/status.php')
    .then(res => res.json())
    .then(status => {
      const hand = status.hand
      hand.forEach(async bone => {
        const { component } = await parseComponent('bone', false, bone)
        container.appendChild(component)
      })
    })
}
