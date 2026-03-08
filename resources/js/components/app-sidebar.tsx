import { Link } from '@inertiajs/react';
import {
  Receipt,
  StorefrontIcon,
  CirclesThreeIcon,
  ChartDonutIcon,
  UsersThreeIcon,
  DevicesIcon,
  GearSixIcon,
  FileTextIcon,
  DownloadIcon,
  UploadIcon,
  KeyIcon,
} from "@phosphor-icons/react"

import {
  Sidebar,
  SidebarContent,
  SidebarFooter,
  SidebarGroup,
  SidebarGroupContent,
  SidebarGroupLabel,
  SidebarHeader,
  SidebarMenu,
  SidebarMenuButton,
  SidebarMenuItem,
} from "@/components/ui/sidebar"
import ApplicationLogo from "@/components/Global/ApplicationLogo"
import { UserNav } from "@/components/user-nav"

// Navigation items
const items = [
  {
    title: "Dashboard",
    url: "dashboard",
    icon: ChartDonutIcon,
  },
  {
    title: "Transactions",
    url: "transactions",
    icon: Receipt,
  },
  {
    title: "Scan Receipt",
    url: "transactions.scan-receipt",
    icon: Receipt,
  },
  {
    title: "Brands",
    url: "brands",
    icon: StorefrontIcon,
  },
  {
    title: "Categories",
    url: "categories",
    icon: CirclesThreeIcon,
  },
  {
    title: "Reports",
    url: "reports.index",
    icon: FileTextIcon,
  },
  {
    title: "Import",
    url: "settings.import",
    icon: UploadIcon,
  },
  {
    title: "Exports",
    url: "exports.index",
    icon: DownloadIcon,
  },
  {
    title: "API Keys",
    url: "settings",
    icon: KeyIcon,
  },
]

const adminItems = [
  {
    title: "Users",
    url: "admin.users.index",
    icon: UsersThreeIcon,
  },
  {
    title: "Sessions",
    url: "sessions.index",
    icon: DevicesIcon,
  },
  {
    title: "Settings",
    url: "admin.settings.index",
    icon: GearSixIcon,
  },
]

interface AppSidebarProps {
  auth?: {
    user: {
      name: string;
      email: string;
      role?: string;
    };
  };
}

export function AppSidebar({ auth }: AppSidebarProps) {
  const isAdmin = auth?.user?.role === 'admin';

  return (
    <Sidebar collapsible="offcanvas" variant="inset">
      <SidebarHeader>
        <SidebarMenu>
          <SidebarMenuItem>
            <SidebarMenuButton size="lg" asChild>
              <Link href="/">
                <ApplicationLogo />
              </Link>
            </SidebarMenuButton>
          </SidebarMenuItem>
        </SidebarMenu>
      </SidebarHeader>
      <SidebarContent>
        <SidebarGroup>
          <SidebarGroupContent>
            <SidebarMenu>
              {items.map((item) => (
                <SidebarMenuItem key={item.title}>
                  <SidebarMenuButton asChild isActive={route().current(item.url)}>
                    <Link href={route(item.url)}>
                      <item.icon />
                      <span>{item.title}</span>
                    </Link>
                  </SidebarMenuButton>
                </SidebarMenuItem>
              ))}
            </SidebarMenu>
          </SidebarGroupContent>
        </SidebarGroup>

        {isAdmin && (
          <SidebarGroup>
            <SidebarGroupLabel>Administration</SidebarGroupLabel>
            <SidebarGroupContent>
              <SidebarMenu>
                {adminItems.map((item) => (
                  <SidebarMenuItem key={item.title}>
                    <SidebarMenuButton asChild isActive={route().current(item.url)}>
                      <Link href={route(item.url)}>
                        <item.icon />
                        <span>{item.title}</span>
                      </Link>
                    </SidebarMenuButton>
                  </SidebarMenuItem>
                ))}
              </SidebarMenu>
            </SidebarGroupContent>
          </SidebarGroup>
        )}
      </SidebarContent>
      <SidebarFooter>
        {auth?.user && <UserNav user={auth.user} />}
      </SidebarFooter>
    </Sidebar>
  )
}
