// src/services/alert.js

import Swal from 'sweetalert2'
import '@/assets/styles/sweetalert-custom.css'

const Toast = Swal.mixin({
  toast: true,
  position: 'top-end',
  showConfirmButton: false,
  timer: 3000,
  timerProgressBar: true,
  customClass: {
    popup: 'swal2-toast-popup'
  }
})

export async function confirmAction({ titre, texte, texteConfirmation }) {
  const result = await Swal.fire({
    title: titre || 'Confirmer l\'action',
    text: texte || 'Êtes-vous sûr de vouloir continuer ?',
    icon: 'warning',
    showCancelButton: true,
    confirmButtonText: texteConfirmation || 'Confirmer',
    cancelButtonText: 'Annuler',
    customClass: {
      confirmButton: 'swal2-confirm-btn',
      cancelButton: 'swal2-cancel-btn'
    }
  })
  return result.isConfirmed
}

export function notifySuccess(message) {
  Toast.fire({
    icon: 'success',
    title: message
  })
}

export function notifyError(message) {
  Toast.fire({
    icon: 'error',
    title: message,
    timer: 4000
  })
}

export function notifyInfo(message) {
  Toast.fire({
    icon: 'info',
    title: message
  })
}