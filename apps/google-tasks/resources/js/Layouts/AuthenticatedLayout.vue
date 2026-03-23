<script setup>
import { computed, ref } from 'vue';
import AppearanceControls from '@/Components/AppearanceControls.vue';
import ApplicationLogo from '@/Components/ApplicationLogo.vue';
import Dropdown from '@/Components/Dropdown.vue';
import DropdownLink from '@/Components/DropdownLink.vue';
import LocaleSwitcher from '@/Components/LocaleSwitcher.vue';
import NavLink from '@/Components/NavLink.vue';
import ResponsiveNavLink from '@/Components/ResponsiveNavLink.vue';
import { commandPaletteRequestOpen } from '@/composables/commandPaletteBridge';
import { Link, usePage } from '@inertiajs/vue3';
import { useI18n } from 'vue-i18n';

const { t } = useI18n();
const page = usePage();
const isTasksIndex = computed(() => page.component === 'Tasks/Index');

const showingNavigationDropdown = ref(false);
</script>

<template>
    <div>
        <div class="min-h-screen bg-gt-canvas">
            <nav
                class="relative border-b border-gt-nav-border bg-gt-nav after:pointer-events-none after:absolute after:inset-x-0 after:bottom-0 after:h-px after:bg-gradient-to-r after:from-transparent after:via-gt-accent/25 after:to-transparent dark:after:via-gt-accent/35"
            >
                <!-- Primary Navigation Menu -->
                <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
                    <div class="flex h-16 justify-between">
                        <div class="flex">
                            <!-- Logo -->
                            <div class="flex shrink-0 items-center">
                                <Link :href="route('dashboard')">
                                    <ApplicationLogo
                                        class="block h-9 w-auto fill-current text-gt-ink"
                                    />
                                </Link>
                            </div>

                            <!-- Navigation Links -->
                            <div
                                class="hidden space-x-8 sm:-my-px sm:ms-10 sm:flex"
                            >
                                <NavLink
                                    :href="route('dashboard')"
                                    :active="route().current('dashboard')"
                                >
                                    {{ t('nav.dashboard') }}
                                </NavLink>
                                <NavLink
                                    :href="route('tasks.index')"
                                    :active="route().current('tasks.index')"
                                >
                                    {{ t('nav.tasks') }}
                                </NavLink>
                            </div>
                        </div>

                        <div
                            class="hidden sm:ms-6 sm:flex sm:items-center sm:gap-4"
                        >
                            <LocaleSwitcher />
                            <AppearanceControls />
                            <!-- Settings Dropdown -->
                            <div class="relative ms-0">
                                <Dropdown align="right" width="48">
                                    <template #trigger>
                                        <span class="inline-flex rounded-md">
                                            <button
                                                type="button"
                                                class="inline-flex items-center rounded-md border border-transparent bg-gt-nav px-3 py-2 text-sm font-medium leading-4 text-gt-muted transition duration-150 ease-in-out hover:text-gt-ink focus:outline-none dark:hover:text-gt-ink"
                                            >
                                                {{ $page.props.auth.user.name }}

                                                <svg
                                                    class="-me-0.5 ms-2 h-4 w-4"
                                                    xmlns="http://www.w3.org/2000/svg"
                                                    viewBox="0 0 20 20"
                                                    fill="currentColor"
                                                >
                                                    <path
                                                        fill-rule="evenodd"
                                                        d="M5.293 7.293a1 1 0 011.414 0L10 10.586l3.293-3.293a1 1 0 111.414 1.414l-4 4a1 1 0 01-1.414 0l-4-4a1 1 0 010-1.414z"
                                                        clip-rule="evenodd"
                                                    />
                                                </svg>
                                            </button>
                                        </span>
                                    </template>

                                    <template #content>
                                        <button
                                            v-if="isTasksIndex"
                                            type="button"
                                            class="block w-full px-4 py-2 text-start text-sm leading-5 text-gt-ink-secondary transition duration-150 ease-in-out hover:bg-gt-field-muted focus:bg-gt-field-muted focus:outline-none dark:text-gt-ink"
                                            @click="commandPaletteRequestOpen()"
                                        >
                                            {{
                                                t(
                                                    'tasks.commandPalette.openFromMenu',
                                                )
                                            }}
                                        </button>
                                        <DropdownLink
                                            :href="route('profile.edit')"
                                        >
                                            {{ t('nav.profile') }}
                                        </DropdownLink>
                                        <DropdownLink
                                            :href="route('logout')"
                                            method="post"
                                            as="button"
                                        >
                                            {{ t('nav.logOut') }}
                                        </DropdownLink>
                                    </template>
                                </Dropdown>
                            </div>
                        </div>

                        <!-- Hamburger -->
                        <div class="-me-2 flex items-center sm:hidden">
                            <button
                                class="inline-flex items-center justify-center rounded-md p-2 text-gt-subtle transition duration-150 ease-in-out hover:bg-gt-field-muted hover:text-gt-muted focus:bg-gt-field-muted focus:text-gt-muted focus:outline-none dark:hover:text-gt-ink"
                                @click="
                                    showingNavigationDropdown =
                                        !showingNavigationDropdown
                                "
                            >
                                <svg
                                    class="h-6 w-6"
                                    stroke="currentColor"
                                    fill="none"
                                    viewBox="0 0 24 24"
                                >
                                    <path
                                        :class="{
                                            hidden: showingNavigationDropdown,
                                            'inline-flex':
                                                !showingNavigationDropdown,
                                        }"
                                        stroke-linecap="round"
                                        stroke-linejoin="round"
                                        stroke-width="2"
                                        d="M4 6h16M4 12h16M4 18h16"
                                    />
                                    <path
                                        :class="{
                                            hidden: !showingNavigationDropdown,
                                            'inline-flex':
                                                showingNavigationDropdown,
                                        }"
                                        stroke-linecap="round"
                                        stroke-linejoin="round"
                                        stroke-width="2"
                                        d="M6 18L18 6M6 6l12 12"
                                    />
                                </svg>
                            </button>
                        </div>
                    </div>
                </div>

                <!-- Responsive Navigation Menu -->
                <div
                    :class="{
                        block: showingNavigationDropdown,
                        hidden: !showingNavigationDropdown,
                    }"
                    class="sm:hidden"
                >
                    <div class="space-y-1 pb-3 pt-2">
                        <ResponsiveNavLink
                            :href="route('dashboard')"
                            :active="route().current('dashboard')"
                        >
                            {{ t('nav.dashboard') }}
                        </ResponsiveNavLink>
                        <ResponsiveNavLink
                            :href="route('tasks.index')"
                            :active="route().current('tasks.index')"
                        >
                            {{ t('nav.tasks') }}
                        </ResponsiveNavLink>
                        <button
                            v-if="isTasksIndex"
                            type="button"
                            class="block w-full border-l-4 border-transparent py-2 pe-4 ps-3 text-start text-base font-medium text-gt-muted transition duration-150 ease-in-out hover:border-gt-border hover:bg-gt-field-muted hover:text-gt-ink focus:bg-gt-field-muted focus:text-gt-ink focus:outline-none"
                            @click="
                                commandPaletteRequestOpen();
                                showingNavigationDropdown = false;
                            "
                        >
                            {{ t('tasks.commandPalette.openFromMenu') }}
                        </button>
                    </div>

                    <!-- Responsive Settings Options -->
                    <div
                        class="border-t border-gt-border pb-1 pt-4"
                    >
                        <div class="px-4">
                            <div
                                class="text-base font-medium text-gt-ink"
                            >
                                {{ $page.props.auth.user.name }}
                            </div>
                            <div class="text-sm font-medium text-gt-muted">
                                {{ $page.props.auth.user.email }}
                            </div>
                        </div>

                        <div class="mt-3 flex flex-wrap items-center gap-3 px-4 pb-2">
                            <LocaleSwitcher />
                            <AppearanceControls />
                        </div>

                        <div class="mt-3 space-y-1">
                            <ResponsiveNavLink :href="route('profile.edit')">
                                {{ t('nav.profile') }}
                            </ResponsiveNavLink>
                            <ResponsiveNavLink
                                :href="route('logout')"
                                method="post"
                                as="button"
                            >
                                {{ t('nav.logOut') }}
                            </ResponsiveNavLink>
                        </div>
                    </div>
                </div>
            </nav>

            <!-- Page Heading -->
            <header
                v-if="$slots.header"
                class="border-b border-gt-nav-border bg-gt-nav shadow-sm dark:shadow-none"
            >
                <div
                    class="mx-auto max-w-7xl px-4 py-3 sm:px-6 sm:py-5 lg:px-8 lg:py-6"
                >
                    <slot name="header" />
                </div>
            </header>

            <!-- Page Content -->
            <main class="min-w-0 overflow-x-hidden">
                <slot />
            </main>
        </div>
    </div>
</template>
