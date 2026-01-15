Page({
  data: {
    name: '',
    phone: '',
    grade: '',
    time: ''
  },
  submit() {
    wx.showToast({ title: '提交成功', icon: 'success' });
  }
});
