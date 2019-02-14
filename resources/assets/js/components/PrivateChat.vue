<template>
  <v-layout row>
    <v-flex class="online-users" xs3>
      <v-list>
        <v-list-tile
          :color="(friend.id == activeFriend)?'green':''"
          v-for="friend in users"
          v-if="user.id != friend.id"
          :key="friend.id"
          @click="activeFriend=friend.id"
        >
          <v-list-tile-action>
            <v-icon color="green">account_circle</v-icon>
          </v-list-tile-action>

          <v-list-tile-content>
            <v-list-tile-title>{{friend.name}}</v-list-tile-title>
          </v-list-tile-content>
        </v-list-tile>
      </v-list>
    </v-flex>
    <v-flex class="messages mb-5" xs9>
      <v-list>
        <v-divider></v-divider>
        <v-list-title class="p-3" v-for="message in allMessages" :key="message.id">
          <v-layout :align-end="(user.id!==message.user_id)" column>
            <v-flex>
              <v-layout column>
                <v-flex>
                  <span class="small font-italic">{{message.user.name}}</span>
                </v-flex>
                <v-flex>
                  <v-chip :color="(user.id!==message.user_id)?'red':'green'" text-color="white">
                    <v-list-title-content>{{message.message}}</v-list-title-content>
                  </v-chip>
                </v-flex>

                <v-flex class="caption font italic">{{message.created_at}}</v-flex>
              </v-layout>
            </v-flex>
          </v-layout>
        </v-list-title>
        <p v-if="typingFriend.name">{{typingFriend.name}} is typing</p>
      </v-list>
      <v-footer height="auto" fixed color="grey">
        <v-layout row>
          <v-flex xs6 offset-xs3 justify-center align-center>
            <v-text-field
              rows="2"
              @keyup.enter="sendMessage"
              v-model="message"
              label="Enter message"
              single-line
              @keydown="onTyping"
            ></v-text-field>
          </v-flex>

          <v-flex xs2>
            <v-btn @click="sendMessage" dark class="mt-3 ml-2 white--text" small color="green">send</v-btn>
          </v-flex>
        </v-layout>
      </v-footer>
    </v-flex>
  </v-layout>
</template>

<script>
export default {
  props: ["user"],
  data() {
    return {
      message: null,
      allMessages: [],
      users: [],
      activeFriend: null,
      typingFriend: {},
      typingClock: null
    };
  },
  watch: {
    activeFriend(val) {
      this.fetchMessages();
    }
  },
  methods: {
    onTyping() {
      Echo.private("privatechat." + this.activeFriend).whisper("typing", {
        user: this.user
      });
    },
    sendMessage() {
      //check if there are messages
      if (!this.message) {
        return alert("please enter the message");
      }

      if (!this.activeFriend) {
        return alert("please active friend");
      }

      //send post request
      axios
        .post("/private-messages/" + this.activeFriend, {
          message: this.message
        })
        .then(response => {
          // console.log(response.data.message);
          this.message = null;
          this.allMessages.push(response.data.message);
          this.fetchMessages();
        });
    },
    fetchMessages() {
      if (!this.activeFriend) {
        return alert("please active friend");
      }

      axios.get("/private-message/" + this.activeFriend).then(response => {
        this.allMessages = response.data;
        setTimeout(this.scrollToEnd, 100);
      });
    },
    fetchUsers() {
      axios.get("/users").then(response => {
        this.users = response.data;
      });
    },
    scrollToEnd() {
      scrollTo(0, 99999);
    }
  },
  mounted() {},
  created() {
    this.fetchUsers();
    Echo.private("privatechat." + this.user.id)
      .listenForWhisper("typing", e => {
        console.log("whispring");
        if (e.user.id == this.activeFriend) {
          this.typingFriend = e.user;
          if (this.typingClock) clearTimeout;
          this.typingClock = setTimeout(() => {
            this.typingFriend = {};
          }, 3000);
          setTimeout(this.scrollToEnd, 100);
        }
      })
      .listen("PrivateMessageSent", e => {
        this.allMessages.push({
          message: e.message.message,
          user: e.user
        });
        setTimeout(this.scrollToEnd, 100);
      });
  }
};
</script>
        
