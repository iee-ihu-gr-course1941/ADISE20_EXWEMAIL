/* eslint-disable-next-line no-unused-expressions */
({ self }) => {
  self.addEventListener('onDataUpdateLocal', ({ detail }) => console.log(detail))
}
