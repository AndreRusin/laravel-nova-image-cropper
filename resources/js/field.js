Nova.booting((Vue, router, store) => {
  Vue.component(
    'index-nova-image-cropper-field',
    require('./components/IndexField').default
  )
  Vue.component(
    'detail-nova-image-cropper-field',
    require('./components/DetailField').default
  )
  Vue.component(
    'form-nova-image-cropper-field',
    require('./components/FormField').default
  )
})
