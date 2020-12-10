/* eslint-disable-next-line no-unused-expressions */
(self) => {
  const p = self.querySelector('.component > p')
  p.onclick = () => { p.innerText = new Date().toLocaleTimeString() }
}
