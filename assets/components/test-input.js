/* eslint-disable-next-line no-unused-expressions */
({ modelText, events: { dispatchDataUpdate } }) => {
  modelText(value => dispatchDataUpdate(value))
}
