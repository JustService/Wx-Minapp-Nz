Page({
  data: {
    name: '冲刺班',
    description: '冲刺重点提升，针对核心题型特训。',
    quota: '剩余名额 12',
    status: '开放报名'
  },
  goLead() {
    wx.navigateTo({ url: '/pages/lead/form/index' });
  }
});
