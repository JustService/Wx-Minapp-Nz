const { schoolLogo } = require('../../utils/assets');

Page({
  data: {
    schoolLogo,
    schoolName: '南召衡辰中学',
    resources: {
      points: 120,
      xp: 48,
      level: 3,
    },
    quickEntrances: [
      { title: '测评中心', desc: '开启专属测评', route: '/pages/evaluate/index' },
      { title: '成长任务', desc: '每日打卡奖励', route: '/pages/growth/index' },
      { title: '权益兑换', desc: '积分换好礼', route: '/pages/benefits/index' },
      { title: '兴趣搭子', desc: '匹配同频伙伴', route: '/pages/buddy/index' }
    ],
    notices: [
      '2024 招生季开启，欢迎预约咨询',
      '衡辰尖子班名额提醒：剩余 84 个',
    ]
  },
  onNavigate(event) {
    const route = event.currentTarget.dataset.route;
    if (route) {
      wx.navigateTo({ url: route });
    }
  }
});
