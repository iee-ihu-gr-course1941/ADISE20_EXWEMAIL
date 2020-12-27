/* eslint-disable-next-line no-unused-expressions */
({ self, events: { dispatchSubmit } }) => {
  const form = self.querySelector('form')

  form.onsubmit = (event) => {
    event.preventDefault()

    if (form.password.value !== form.confirmPassword.value) {
      alert('Passwords don\'t match')
      return
    }

    dispatchSubmit({
      username: form.username.value,
      password: form.password.value,
      $action: form.getAttribute('action'),
      $method: form.getAttribute('method')
    })
  }
}
