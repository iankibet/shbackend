import { viteBundler } from '@vuepress/bundler-vite'
import { defaultTheme } from '@vuepress/theme-default'
import { defineUserConfig } from 'vuepress'

export default defineUserConfig({
    bundler: viteBundler(),
    lang: 'en-US',
    title: 'Sh Backend!',
    description: 'SH backend documentation!',
    sidebar: 'auto',
    themeConfig: {
        sidebar: 'auto'
    },
    theme: defaultTheme({
        colorMode: 'auto',
        colorModeSwitch: true,
        navbar: [
            {
                text: 'Documentation',
                link: '/guide/introduction'
            },
            {
                text: 'Backend Docs',
                link: 'https://backend-documentation.pages.dev/'
            },
            {
                text: 'Github',
                link: 'https://github.com/sharasolns/sh-frontend'
            }
        ],
        sidebar: {
            '/guide':[
                {
                    text:'Guide',
                    collapsible: false,
                    children: [
                        "/guide/introduction",

                    ]
                },
                {
                    text:'Basics',
                    collapsible: true,
                    children: [
                        "/guide/basics/basics",
                        "/guide/basics/basics/models",
                        "/guide/basics/basics/controllers",
                        "/guide/basics/basics/routes",
                    ]
                },
                {
                    text:'Commands',
                    collapsible: true,
                    hideText: true,
                    children: [
                        "/guide/commands/commands",
                        "/guide/commands/shinit",
                        "/guide/commands/generatemodel",
                        "/guide/commands/makeapiendpoint",
                    ]
                },
                {
                    text:'Helpers',
                    collapsible: true,
                    children: [
                        "/guide/helpers/pagination/pagination"
                    ]
                },
                {
                    text:'About',
                    collapsible: false,
                    children: [
                        "/guide/about/overview",
                        "/guide/about/team"
                    ]
                }
            ]
        }
    })
})
