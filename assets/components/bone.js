/* eslint-disable-next-line no-unused-expressions */
({ self, props }) => {
  const left = self.querySelector('.bone-dots-left')
  const right = self.querySelector('.bone-dots-right')

  left.setAttribute('data-base-bone', props[0])
  right.setAttribute('data-base-bone', props[1])
}
