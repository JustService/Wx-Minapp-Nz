Page({
  data: {
    current: 1,
    total: 3,
    question: {
      title: '面对难题时我会？',
      options: ['坚持拆解直到搞懂', '会查资料再继续', '先放一放']
    }
  },
  selectOption() {
    wx.navigateTo({ url: '/pages/evaluate/report/index' });
  }
});
