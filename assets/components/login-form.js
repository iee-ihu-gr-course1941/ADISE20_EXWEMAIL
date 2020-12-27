/* eslint-disable-next-line no-unused-expressions */
({ self }) => {
  self.addEventListener(
    'onLoginSubmit',
    (args) => callback(args)
      .then(data => alert(`Logged in as ${data.username}`))
      .catch(err => console.error(err))
  )
  self.addEventListener(
    'onRegisterSubmit',
    (args) => callback(args)
      .then(data => alert(`User ${data.username} successfully registered`))
      .catch(err => console.error(err))
  )

  function callback ({ detail }) {
    const login = new URLSearchParams()
    login.append('username', detail.username)
    login.append('password', detail.password)

    return fetch(detail.$action, {
      method: detail.$method,
      body: login
    }).then(res => res.json())
  }
}
