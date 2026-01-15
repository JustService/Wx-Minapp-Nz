Page({
  data: {
    tags: ['电竞策略', '数学冲刺', '早起打卡'],
    candidate: {
      name: '夜幕战士',
      reason: ['共同标签：电竞策略', '学习节奏相近']
    },
    greetings: ['一起完成今日任务吧！', '你好，我也在冲刺班', '一起组队拿积分']
  },
  goEditTags() {
    wx.showToast({ title: '标签编辑已保存', icon: 'none' });
  }
});
