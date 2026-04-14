import { useNotification } from '@kyvg/vue3-notification'

const { notify } = useNotification()

export default function useNotificationStore() {
  function successToast(title = 'Success', text = 'Request was processed successfully') {
    notify({
      title,
      text,
      type: 'success'
    })
  }

  function errorToast(title = 'Error', text = 'Something went wrong, try again later') {
    if (notify) {
      notify({
        title,
        text,
        type: 'error'
      })
    }
  }

  function warningToast(title = 'Warning', text = 'It seems something is not right') {
    notify({
      title,
      text,
      type: 'warn'
    })
  }

  return {
    successToast,
    errorToast,
    warningToast
  }
}

