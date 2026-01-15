const { schoolLogo } = require('../../utils/assets');

Page({
  data: {
    schoolLogo,
    profile: {
      name: '衡辰学员',
      grade: '初三',
      city: '南召'
    },
    menu: [
      { title: '测评报告', desc: '查看历史报告' },
      { title: '我的权益券', desc: '兑换记录' },
      { title: '兴趣标签', desc: '管理兴趣标签' }
    ]
  }
});
