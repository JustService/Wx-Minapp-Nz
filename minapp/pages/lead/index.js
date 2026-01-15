Page({
  data: {
    form: {
      name: '',
      phone: '',
      grade: '',
      appointment: ''
    }
  },
  onInput(event) {
    const key = event.currentTarget.dataset.key;
    this.setData({ [`form.${key}`]: event.detail.value });
  },
  onSubmit() {
    wx.showToast({ title: '已提交预约', icon: 'success' });
  }
});
