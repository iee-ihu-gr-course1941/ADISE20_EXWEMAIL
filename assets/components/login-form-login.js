/* eslint-disable-next-line no-unused-expressions */
({ self, events: { dispatchSubmit } }) => {
  const form = self.querySelector('form')

  form.onsubmit = (event) => {
    event.preventDefault()
    dispatchSubmit({
      username: form.username.value,
      password: form.password.value,
      $action: form.getAttribute('action'),
      $method: form.getAttribute('method')
    })
  }
}
