/* eslint-disable-next-line no-unused-expressions */
(self) => {
  const p = self.querySelector('.component-body > p')
  p.onclick = () => { p.innerText = new Date().toLocaleTimeString() }
}
