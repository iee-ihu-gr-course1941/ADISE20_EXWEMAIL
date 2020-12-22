/* eslint-disable-next-line no-unused-expressions */
({ self, setTestContent }) => {
  const p = self.querySelector('.component-body > p.click')
  p.onclick = () => { setTestContent(new Date().toLocaleTimeString()) }
}
